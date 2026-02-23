/**
 * Configuration globale de l'application mobile
 * Les URLs sont résolues selon l'environnement (dev/staging/prod).
 */

import Constants from 'expo-constants';

const ENV = Constants.expoConfig?.extra?.APP_ENV ?? 'development';

const configs = {
  development: {
    /** IP de ton PC sur le réseau Wi‑Fi (ipconfig → Adresse IPv4). Port 8088 = site web. */
    API_BASE_URL: 'http://192.168.1.55:8088/api/v1',
    APP_NAME:     'Fact2PDF [DEV]',
  },
  staging: {
    API_BASE_URL: 'https://staging.fact2pdf.app/api/v1',
    APP_NAME:     'Fact2PDF [STAGING]',
  },
  production: {
    API_BASE_URL: 'https://fact2pdf.app/api/v1',
    APP_NAME:     'Fact2PDF',
  },
} as const;

export const Config = configs[ENV as keyof typeof configs] ?? configs.development;

/** URL du site web (sans /api/v1) pour charger les assets : logos, etc. */
export const SITE_BASE_URL = Config.API_BASE_URL.replace(/\/api\/v1\/?$/, '');

/** Retourne l’URL complète d’un asset (ex. logo_path /storage/uploads/logos/xxx.webp). */
export function getAssetUrl(path: string | null | undefined): string | null {
  if (!path || !path.trim()) return null;
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  const base = SITE_BASE_URL.replace(/\/$/, '');
  const p = path.startsWith('/') ? path : `/${path}`;
  return `${base}${p}`;
}

/** Clé AsyncStorage / SecureStore pour le JWT */
export const TOKEN_KEY     = 'f2p_jwt_token';
export const USER_KEY      = 'f2p_user';

/** Pagination */
export const PAGE_SIZE = 20;

/** Upload */
export const MAX_IMAGE_SIZE_MB = 5;

/** Polling notifications (ms) */
export const NOTIF_POLL_INTERVAL = 30_000;
