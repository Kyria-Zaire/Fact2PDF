/**
 * useClients — FlatList infinite scroll with Realm offline cache
 *
 * Strategy:
 *  1. On mount: load cached clients from Realm (instant render)
 *  2. Then fetch page 1 from API (online only) → upsert into Realm
 *  3. loadMore() fetches next pages
 */

import { useState, useEffect, useCallback, useRef } from 'react';
import { ClientsApi, ApiClient } from '@/services/api';
import { getRealmInstance }      from '@/store/realm';
import { PAGE_SIZE }             from '@/constants/config';

export interface UseClientsResult {
  clients:     ApiClient[];
  loading:     boolean;
  refreshing:  boolean;
  hasMore:     boolean;
  error:       string | null;
  loadMore:    () => void;
  refresh:     () => void;
}

export function useClients(): UseClientsResult {
  const [clients,    setClients]    = useState<ApiClient[]>([]);
  const [loading,    setLoading]    = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [hasMore,    setHasMore]    = useState(true);
  const [error,      setError]      = useState<string | null>(null);

  const pageRef     = useRef(1);
  const fetchingRef = useRef(false);

  /** Load cached data from Realm for immediate display */
  async function loadFromCache() {
    try {
      const realm  = await getRealmInstance();
      const cached = realm.objects('Client').sorted('name');
      if (cached.length > 0) {
        // Convert Realm objects to plain ApiClient
        const plain: ApiClient[] = cached.map((c: any) => ({
          id:          c._id,
          name:        c.name,
          email:       c.email ?? null,
          phone:       c.phone ?? null,
          address:     c.address ?? null,
          city:        c.city ?? null,
          postal_code: c.postal_code ?? null,
          country:     c.country,
          logo_path:   c.logo_path ?? null,
          notes:       c.notes ?? null,
          invoice_count: 0,
          total_billed:  0,
        }));
        setClients(plain);
      }
    } catch {
      // Cache miss is non-fatal
    }
  }

  /** Upsert Realm with fresh API data */
  async function upsertCache(items: ApiClient[]) {
    try {
      const realm = await getRealmInstance();
      realm.write(() => {
        for (const c of items) {
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
    } catch {
      // Cache write failure is non-fatal
    }
  }

  async function fetchPage(page: number, isRefresh = false) {
    if (fetchingRef.current) return;
    fetchingRef.current = true;
    try {
      const data = await ClientsApi.list({ page, limit: PAGE_SIZE });
      const items: ApiClient[] = Array.isArray(data) ? data : (data as any).data ?? data;

      setClients(prev => isRefresh ? items : [...prev, ...items]);
      setHasMore(items.length >= PAGE_SIZE);
      setError(null);

      // Upsert background (no await — fire and forget)
      upsertCache(items);
    } catch (err: any) {
      setError(err?.message ?? 'Erreur réseau');
    } finally {
      fetchingRef.current = false;
      setLoading(false);
      setRefreshing(false);
    }
  }

  useEffect(() => {
    loadFromCache().then(() => fetchPage(1));
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const loadMore = useCallback(() => {
    if (!hasMore || fetchingRef.current || loading) return;
    pageRef.current += 1;
    fetchPage(pageRef.current);
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [hasMore, loading]);

  const refresh = useCallback(() => {
    pageRef.current = 1;
    setRefreshing(true);
    setHasMore(true);
    fetchPage(1, true);
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return { clients, loading, refreshing, hasMore, error, loadMore, refresh };
}
