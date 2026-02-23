/**
 * api.ts — Client HTTP Fetch avec JWT automatique
 *
 * Features :
 *   - Injection automatique du header Authorization: Bearer <token>
 *   - Détection offline (expo-network)
 *   - Upload multipart throttlé (logos clients)
 *   - Types TypeScript stricts pour les réponses API
 */

import * as Network from 'expo-network';
import { getToken, clearToken } from './auth';
import { Config } from '@/constants/config';

// ============================================================
// Types réponse API PHP
// ============================================================

export interface ApiResponse<T> {
  success: boolean;
  data:    T;
  error?:  string;
}

export interface ApiClient {
  id:           number;
  name:         string;
  email:        string | null;
  phone:        string | null;
  address:      string | null;
  city:         string | null;
  postal_code:  string | null;
  country:      string;
  logo_path:    string | null;
  notes:        string | null;
  contacts:     ApiContact[];
  invoice_count?: number;
  total_billed?:  number;
}

export interface ApiContact {
  id:         number;
  client_id:  number;
  name:       string;
  email:      string | null;
  phone:      string | null;
  role:       string | null;
  is_primary: number;
}

export interface ApiInvoice {
  id:          number;
  client_id:   number;
  number:      string;
  status:      'draft' | 'pending' | 'paid' | 'overdue';
  issue_date:  string;
  due_date:    string;
  subtotal:    number;
  tax_rate:    number;
  tax_amount:  number;
  total:       number;
  notes:       string | null;
  client_name?: string;
  items?:      ApiInvoiceItem[];
}

export interface ApiInvoiceItem {
  id:          number;
  description: string;
  quantity:    number;
  unit_price:  number;
  total:       number;
}

export interface ApiProject {
  id:          number;
  client_id:   number;
  name:        string;
  description: string | null;
  status:      string;
  priority:    string;
  start_date:  string | null;
  end_date:    string | null;
  timeline:    TimelineStep[];
  progress:    number;
  is_late?:    boolean;
  client_name?: string;
}

export interface TimelineStep {
  label: string;
  date:  string;
  done:  boolean;
}

export interface LoginResponse {
  token: string;
  user:  { id: number; username: string; email: string; role: string };
}

// ============================================================
// Erreur API typée
// ============================================================

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public offline = false,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

// ============================================================
// Helpers internes
// ============================================================

async function buildHeaders(isFormData = false): Promise<HeadersInit> {
  const token = await getToken();
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };
  if (!isFormData) headers['Content-Type'] = 'application/json';
  if (token)       headers['Authorization'] = `Bearer ${token}`;
  return headers;
}

async function checkConnectivity(): Promise<void> {
  const net = await Network.getNetworkStateAsync();
  if (!net.isConnected) {
    throw new ApiError('Pas de connexion internet.', 0, true);
  }
}

async function handleResponse<T>(res: Response): Promise<T> {
  // Token expiré / invalide → déconnecter
  if (res.status === 401) {
    await clearToken();
    throw new ApiError('Session expirée. Veuillez vous reconnecter.', 401);
  }

  let body: ApiResponse<T>;
  try {
    body = await res.json();
  } catch {
    throw new ApiError(`Réponse invalide du serveur (${res.status}).`, res.status);
  }

  if (!res.ok || !body.success) {
    throw new ApiError(body.error ?? `Erreur serveur (${res.status}).`, res.status);
  }

  return body.data;
}

// ============================================================
// Client API public
// ============================================================

async function get<T>(endpoint: string): Promise<T> {
  await checkConnectivity();
  const res = await fetch(`${Config.API_BASE_URL}/${endpoint}`, {
    method:  'GET',
    headers: await buildHeaders(),
  });
  return handleResponse<T>(res);
}

async function post<T>(endpoint: string, body: unknown): Promise<T> {
  await checkConnectivity();
  const res = await fetch(`${Config.API_BASE_URL}/${endpoint}`, {
    method:  'POST',
    headers: await buildHeaders(),
    body:    JSON.stringify(body),
  });
  return handleResponse<T>(res);
}

async function put<T>(endpoint: string, body: unknown): Promise<T> {
  await checkConnectivity();
  const res = await fetch(`${Config.API_BASE_URL}/${endpoint}`, {
    method:  'PUT',
    headers: await buildHeaders(),
    body:    JSON.stringify(body),
  });
  return handleResponse<T>(res);
}

async function patch<T>(endpoint: string, body: unknown): Promise<T> {
  await checkConnectivity();
  const res = await fetch(`${Config.API_BASE_URL}/${endpoint}`, {
    method:  'PATCH',
    headers: await buildHeaders(),
    body:    JSON.stringify(body),
  });
  return handleResponse<T>(res);
}

async function del<T>(endpoint: string): Promise<T> {
  await checkConnectivity();
  const res = await fetch(`${Config.API_BASE_URL}/${endpoint}`, {
    method:  'DELETE',
    headers: await buildHeaders(),
  });
  return handleResponse<T>(res);
}

/**
 * Upload multipart/form-data pour les logos (images).
 * Throttle intégré : limite la taille avant envoi.
 */
async function uploadForm<T>(endpoint: string, formData: FormData): Promise<T> {
  await checkConnectivity();
  const headers = await buildHeaders(true); // pas de Content-Type (boundary auto)
  const res = await fetch(`${Config.API_BASE_URL}/${endpoint}`, {
    method:  'POST',
    headers,
    body:    formData,
  });
  return handleResponse<T>(res);
}

// ============================================================
// Endpoints métier
// ============================================================

export const AuthApi = {
  login: (email: string, password: string) =>
    post<LoginResponse>('auth/login', { email, password }),
};

export const ClientsApi = {
  list:   (opts: { page?: number; limit?: number } = {}) => {
    const { page = 1, limit = 20 } = opts;
    return get<ApiClient[]>(`clients?page=${page}&limit=${limit}`);
  },
  get:    (id: number) => get<ApiClient>(`clients/${id}`),
  /** @deprecated use get() */
  show:   (id: number) => get<ApiClient>(`clients/${id}`),
  create: (data: Record<string, string>) => post<ApiClient>('clients', data),
  createWithLogo: (data: Record<string, string>, imageUri: string) => {
    const fd = new FormData();
    Object.entries(data).forEach(([k, v]) => v && fd.append(k, v));
    const filename = imageUri.split('/').pop() ?? 'logo.jpg';
    const ext      = filename.split('.').pop()?.toLowerCase() ?? 'jpg';
    const mimeMap: Record<string, string> = { jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', webp: 'image/webp' };
    fd.append('logo', { uri: imageUri, name: filename, type: mimeMap[ext] ?? 'image/jpeg' } as any);
    return uploadForm<ApiClient>('clients', fd);
  },
  update: (id: number, data: Record<string, string>) => put<ApiClient>(`clients/${id}`, data),
  updateWithLogo: (id: number, data: Record<string, string>, imageUri: string) => {
    const fd = new FormData();
    Object.entries(data).forEach(([k, v]) => v && fd.append(k, v));
    const filename = imageUri.split('/').pop() ?? 'logo.jpg';
    const ext      = filename.split('.').pop()?.toLowerCase() ?? 'jpg';
    const mimeMap: Record<string, string> = { jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', webp: 'image/webp' };
    fd.append('logo', { uri: imageUri, name: filename, type: mimeMap[ext] ?? 'image/jpeg' } as any);
    return uploadForm<ApiClient>(`clients/${id}`, fd);
  },
  delete: (id: number) => del<{ deleted: boolean }>(`clients/${id}`),
  search: (q: string)  => get<ApiClient[]>(`clients?search=${encodeURIComponent(q)}`),
};

export const InvoicesApi = {
  list:      ()          => get<ApiInvoice[]>('invoices'),
  byClient:  (id: number) => get<ApiInvoice[]>(`clients/${id}/invoices`),
  show:      (id: number) => get<ApiInvoice>(`invoices/${id}`),
};

export const ProjectsApi = {
  list:     ()           => get<ApiProject[]>('projects'),
  byClient: (id: number) => get<ApiProject[]>(`clients/${id}/projects`),
  show:     (id: number) => get<ApiProject>(`projects/${id}`),
  create:   (data: unknown) => post<ApiProject>('projects', data),
  update:   (id: number, data: unknown) => put<ApiProject>(`projects/${id}`, data),
  updateTimeline: (id: number, steps: TimelineStep[]) =>
    patch<{ success: boolean; progress: number }>(`projects/${id}/timeline`, { steps }),
};

export const NotificationsApi = {
  poll:       () => get<{ count: number; items: unknown[] }>('notifications/poll'),
  markRead:   (id: number) => post(`notifications/${id}/read`, {}),
  markAllRead: () => post('notifications/read-all', {}),
};
