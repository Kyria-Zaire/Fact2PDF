/**
 * notifications.ts — Push notifications via Expo Notifications
 *
 * - Demande la permission au premier lancement
 * - Enregistre le push token sur le serveur
 * - En Expo Go (SDK 53+) les push sont désactivés → on skip l'inscription
 */

import * as Notifications from 'expo-notifications';
import * as Device from 'expo-device';
import Constants from 'expo-constants';
import { Platform } from 'react-native';
import { NotificationsApi } from './api';
import { NOTIF_POLL_INTERVAL } from '@/constants/config';

/** Expo Go ne supporte plus les push (SDK 53+) → pas d'inscription token */
function isExpoGo(): boolean {
  return Constants.appOwnership === 'expo';
}

/** UUID v4 simple — projectId EAS doit être un vrai UUID, pas le placeholder */
const UUID_REGEX = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
function hasValidEasProjectId(): boolean {
  const id = Constants.expoConfig?.extra?.eas?.projectId ?? Constants.easConfig?.projectId;
  return typeof id === 'string' && UUID_REGEX.test(id);
}

// Comportement en foreground : afficher la notification + son
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert:  true,
    shouldPlaySound:  true,
    shouldSetBadge:   true,
    shouldShowBanner: true,
    shouldShowList:   true,
  }),
});

/**
 * Demande la permission et retourne le token Expo Push.
 * À appeler au démarrage de l'app (après login).
 */
export async function registerForPushNotifications(): Promise<string | null> {
  if (isExpoGo()) {
    return null; // Push non supportés dans Expo Go (SDK 53+)
  }
  if (!hasValidEasProjectId()) {
    return null; // Pas de projectId EAS valide → évite l'erreur 400 "Invalid uuid"
  }
  if (!Device.isDevice) {
    return null;
  }

  // Vérifier la permission existante
  const { status: existing } = await Notifications.getPermissionsAsync();
  let finalStatus = existing;

  if (existing !== 'granted') {
    const { status } = await Notifications.requestPermissionsAsync();
    finalStatus = status;
  }

  if (finalStatus !== 'granted') {
    console.warn('[Notif] Permission refusée.');
    return null;
  }

  // Canal Android (obligatoire Android 8+)
  if (Platform.OS === 'android') {
    await Notifications.setNotificationChannelAsync('default', {
      name:                'Fact2PDF',
      importance:          Notifications.AndroidImportance.MAX,
      vibrationPattern:    [0, 250, 250, 250],
      lightColor:          '#0d6efd',
      lockscreenVisibility: Notifications.AndroidNotificationVisibility.PUBLIC,
      bypassDnd:           false,
    });
  }

  try {
    const token = (await Notifications.getExpoPushTokenAsync()).data;
    return token;
  } catch (err) {
    console.error('[Notif] Erreur récupération token:', err);
    return null;
  }
}

/**
 * Polling léger (fallback si push indisponible) :
 * interroge GET /notifications/poll toutes les N secondes.
 * Retourne une fonction cleanup pour arrêter le polling.
 */
export function startNotificationPolling(
  onNew: (count: number, items: unknown[]) => void,
): () => void {
  let lastCount = 0;

  const poll = async () => {
    try {
      const data = await NotificationsApi.poll();
      if (data.count > lastCount) {
        onNew(data.count, data.items);
      }
      lastCount = data.count;
    } catch {
      // Silencieux (offline ou erreur réseau)
    }
  };

  poll(); // immédiat
  const id = setInterval(poll, NOTIF_POLL_INTERVAL);

  return () => clearInterval(id);
}

/**
 * Abonne aux événements de notification reçue (foreground).
 * Retourne une fonction cleanup.
 */
export function subscribeToNotifications(
  onReceive: (notif: Notifications.Notification) => void,
  onResponse?: (response: Notifications.NotificationResponse) => void,
): () => void {
  const receiveSub  = Notifications.addNotificationReceivedListener(onReceive);
  const responseSub = onResponse
    ? Notifications.addNotificationResponseReceivedListener(onResponse)
    : null;

  return () => {
    receiveSub.remove();
    responseSub?.remove();
  };
}

/** Affiche une notification locale (debug / offline). */
export async function showLocalNotification(title: string, body: string): Promise<void> {
  await Notifications.scheduleNotificationAsync({
    content: { title, body, sound: true },
    trigger: null, // Immédiat
  });
}
