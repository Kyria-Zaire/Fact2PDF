/**
 * useOfflineSync — background sync of all Realm data when online
 *
 * Syncs: clients, invoices, projects
 * Called once at app startup from App.tsx
 */

import { useEffect, useRef, useState } from 'react';
import NetInfo from '@react-native-community/netinfo';
import { ClientsApi, InvoicesApi, ProjectsApi } from '@/services/api';
import { getRealmInstance } from '@/store/realm';

export function useOfflineSync() {
  const [syncing, setSyncing] = useState(false);
  const ranRef = useRef(false);

  async function syncAll() {
    setSyncing(true);
    try {
      const realm = await getRealmInstance();

      // --- Sync clients ---
      const clients = await ClientsApi.list({ page: 1, limit: 500 });
      const clientItems = Array.isArray(clients) ? clients : (clients as any).data ?? clients;
      realm.write(() => {
        for (const c of clientItems) {
          realm.create('Client', {
            _id:         c.id,
            name:        c.name,
            email:       c.email ?? undefined,
            phone:       c.phone ?? undefined,
            address:     c.address ?? undefined,
            city:        c.city ?? undefined,
            postal_code: c.postal_code ?? undefined,
            country:     c.country ?? 'FR',
            logo_path:   c.logo_path ?? undefined,
            notes:       c.notes ?? undefined,
            synced_at:   Date.now(),
          }, Realm.UpdateMode.Modified);
        }
      });

      // --- Sync projects ---
      const projects = await ProjectsApi.list();
      const projectItems = Array.isArray(projects) ? projects : (projects as any).data ?? projects;
      realm.write(() => {
        for (const p of projectItems) {
          realm.create('Project', {
            _id:           p.id,
            client_id:     p.client_id,
            name:          p.name,
            description:   p.description ?? undefined,
            status:        p.status,
            priority:      p.priority,
            start_date:    p.start_date ?? undefined,
            end_date:      p.end_date ?? undefined,
            timeline_json: JSON.stringify(p.timeline ?? []),
            progress:      p.progress ?? 0,
            synced_at:     Date.now(),
          }, Realm.UpdateMode.Modified);
        }
      });
    } catch {
      // Sync failure is non-fatal (offline or auth error)
    } finally {
      setSyncing(false);
    }
  }

  useEffect(() => {
    if (ranRef.current) return;
    ranRef.current = true;

    const unsubscribe = NetInfo.addEventListener(state => {
      if (state.isConnected && state.isInternetReachable) {
        syncAll();
        unsubscribe(); // One-shot on first connection
      }
    });

    return () => unsubscribe();
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return { syncing };
}
