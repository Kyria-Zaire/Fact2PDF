/**
 * useInvoices — fetch invoices for a client with Realm cache
 */

import { useState, useEffect, useCallback } from 'react';
import { InvoicesApi, ApiInvoice } from '@/services/api';
import { getRealmInstance }        from '@/store/realm';

export function useInvoices(clientId: number) {
  const [invoices,   setInvoices]   = useState<ApiInvoice[]>([]);
  const [loading,    setLoading]    = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error,      setError]      = useState<string | null>(null);

  async function loadFromCache() {
    try {
      const realm  = await getRealmInstance();
      const cached = realm.objects('Invoice')
        .filtered('client_id == $0', clientId)
        .sorted('issue_date', true);

      if (cached.length > 0) {
        const plain: ApiInvoice[] = cached.map((inv: any) => ({
          id:         inv._id,
          client_id:  inv.client_id,
          number:     inv.number,
          status:     inv.status,
          issue_date: inv.issue_date,
          due_date:   inv.due_date,
          subtotal:   inv.subtotal,
          tax_rate:   inv.tax_rate,
          tax_amount: inv.tax_amount,
          total:      inv.total,
          notes:      inv.notes ?? null,
          client_name: '',
        }));
        setInvoices(plain);
      }
    } catch { /* non-fatal */ }
  }

  async function fetchFromApi(isRefresh = false) {
    try {
      const data = await InvoicesApi.byClient(clientId);
      const items: ApiInvoice[] = Array.isArray(data) ? data : (data as any).data ?? data;

      setInvoices(isRefresh ? items : items);
      setError(null);

      // Cache upsert
      const realm = await getRealmInstance();
      realm.write(() => {
        for (const inv of items) {
          realm.create('Invoice', {
            _id:        inv.id,
            client_id:  inv.client_id,
            number:     inv.number,
            status:     inv.status,
            issue_date: inv.issue_date,
            due_date:   inv.due_date,
            subtotal:   inv.subtotal,
            tax_rate:   inv.tax_rate,
            tax_amount: inv.tax_amount,
            total:      inv.total,
            notes:      inv.notes ?? undefined,
            synced_at:  Date.now(),
          }, Realm.UpdateMode.Modified);
        }
      });
    } catch (err: any) {
      setError(err?.message ?? 'Erreur réseau');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }

  useEffect(() => {
    loadFromCache().then(() => fetchFromApi());
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [clientId]);

  const refresh = useCallback(() => {
    setRefreshing(true);
    fetchFromApi(true);
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [clientId]);

  return { invoices, loading, refreshing, error, refresh };
}
