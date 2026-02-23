/**
 * Configuration globale de l'application mobile
 * Les URLs sont résolues selon l'environnement (dev/staging/prod).
 */

import Constants from 'expo-constants';

const ENV = Constants.expoConfig?.extra?.APP_ENV ?? 'development';

const configs = {
  development: {
    /** Remplacer par l'IP locale de votre machine Docker (pas localhost = iOS simulator) */
    API_BASE_URL: 'http://192.168.1.100:8080/api/v1',
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

/** Clé AsyncStorage / SecureStore pour le JWT */
export const TOKEN_KEY     = 'f2p_jwt_token';
export const USER_KEY      = 'f2p_user';

/** Pagination */
export const PAGE_SIZE = 20;

/** Upload */
export const MAX_IMAGE_SIZE_MB = 5;

/** Polling notifications (ms) */
export const NOTIF_POLL_INTERVAL = 30_000;
