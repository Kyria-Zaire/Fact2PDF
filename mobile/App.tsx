/**
 * App.tsx — Entry point Expo
 *
 * - Initialise les notifications (canal Android + token push)
 * - Lance le sync offline en arrière-plan
 * - Monte le NavigationContainer via RootNavigator
 */

import React, { useEffect } from 'react';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import RootNavigator from '@/navigation';
import { AuthProvider } from '@/contexts/AuthContext';
import { registerForPushNotifications, startNotificationPolling } from '@/services/notifications';
import { useOfflineSync } from '@/hooks/useOfflineSync';

function AppInner() {
  useOfflineSync();

  useEffect(() => {
    // Push notification registration (non-blocking)
    registerForPushNotifications().catch(() => {});
    const stopPolling = startNotificationPolling(() => {});
    return () => stopPolling();
  }, []);

  return (
    <>
      <StatusBar style="light" />
      <RootNavigator />
    </>
  );
}

export default function App() {
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <AppInner />
      </AuthProvider>
    </SafeAreaProvider>
  );
}
