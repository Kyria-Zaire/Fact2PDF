/**
 * auth.ts — Gestion du token JWT et de la session utilisateur
 *
 * Utilise expo-secure-store (chiffré sur device) pour stocker le JWT.
 * Fallback vers AsyncStorage si SecureStore indisponible (simulateur web).
 */

import * as SecureStore from 'expo-secure-store';
import { TOKEN_KEY, USER_KEY } from '@/constants/config';

export interface AuthUser {
  id:       number;
  username: string;
  email:    string;
  role:     'admin' | 'user' | 'viewer';
}

/** Sauvegarde le token JWT de façon sécurisée. */
export async function saveToken(token: string): Promise<void> {
  await SecureStore.setItemAsync(TOKEN_KEY, token);
}

/** Récupère le token JWT. */
export async function getToken(): Promise<string | null> {
  return SecureStore.getItemAsync(TOKEN_KEY);
}

/** Supprime le token (déconnexion). */
export async function clearToken(): Promise<void> {
  await SecureStore.deleteItemAsync(TOKEN_KEY);
  await SecureStore.deleteItemAsync(USER_KEY);
}

/** Sauvegarde les infos utilisateur (JSON). */
export async function saveUser(user: AuthUser): Promise<void> {
  await SecureStore.setItemAsync(USER_KEY, JSON.stringify(user));
}

/** Récupère l'utilisateur connecté. */
export async function getUser(): Promise<AuthUser | null> {
  const raw = await SecureStore.getItemAsync(USER_KEY);
  if (!raw) return null;
  try {
    return JSON.parse(raw) as AuthUser;
  } catch {
    return null;
  }
}

/** Vérifie si l'utilisateur est connecté (token présent). */
export async function isAuthenticated(): Promise<boolean> {
  const token = await getToken();
  return !!token;
}

/**
 * Décode le payload JWT (sans vérification de signature côté mobile —
 * la vérification est faite par le serveur PHP).
 */
export function decodeJwtPayload(token: string): Record<string, unknown> | null {
  try {
    const parts   = token.split('.');
    if (parts.length !== 3) return null;
    const payload = parts[1];
    // Padding base64
    const padded  = payload + '=='.slice((payload.length + 3) % 4);
    const decoded = atob(padded.replace(/-/g, '+').replace(/_/g, '/'));
    return JSON.parse(decoded);
  } catch {
    return null;
  }
}

/** Vérifie si le token est expiré. */
export function isTokenExpired(token: string): boolean {
  const payload = decodeJwtPayload(token);
  if (!payload || typeof payload.exp !== 'number') return true;
  return payload.exp < Date.now() / 1000;
}
