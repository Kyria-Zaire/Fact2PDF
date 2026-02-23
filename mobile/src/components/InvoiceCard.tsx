/**
 * InvoiceCard — row card for invoice list
 */

import React, { memo } from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { ApiInvoice } from '@/services/api';
import { Colors }     from '@/constants/colors';

const STATUS_LABELS: Record<string, string> = {
  draft:   'Brouillon',
  pending: 'En attente',
  paid:    'Payée',
  overdue: 'En retard',
};

/** Couleurs par statut : fond + texte pour un badge lisible */
function getStatusStyle(status: string): { badge: object; text: object } {
  const map: Record<string, { bg: string; text: string }> = {
    draft:   { bg: '#e9ecef', text: '#495057' },
    pending: { bg: '#fff3cd', text: '#856404' },
    paid:    { bg: '#d1e7dd', text: '#0f5132' },
    overdue: { bg: '#f8d7da', text: '#842029' },
  };
  const s = map[status] ?? { bg: Colors.gray, text: Colors.white };
  return {
    badge: { backgroundColor: s.bg },
    text:  { color: s.text, fontWeight: '600' as const },
  };
}

interface Props {
  invoice: ApiInvoice;
  onPress?: (invoice: ApiInvoice) => void;
}

function InvoiceCard({ invoice, onPress }: Props) {
  const statusStyle = getStatusStyle(invoice.status);
  const isOverdue = invoice.status === 'overdue';

  return (
    <TouchableOpacity
      style={[styles.card, isOverdue && styles.cardOverdue]}
      onPress={() => onPress?.(invoice)}
      activeOpacity={onPress ? 0.75 : 1}
    >
      <View style={styles.left}>
        <Text style={styles.number}>{invoice.number}</Text>
        <Text style={styles.date}>{formatDate(invoice.issue_date)}</Text>
        <Text style={[styles.due, isOverdue && styles.dueOverdue]}>
          Échéance : {formatDate(invoice.due_date)}
        </Text>
      </View>

      <View style={styles.right}>
        <Text style={styles.total}>
          {Number(invoice.total).toLocaleString('fr-FR', {
            style: 'currency', currency: 'EUR',
          })}
        </Text>
        <View style={[styles.badge, statusStyle.badge]}>
          <Text style={[styles.badgeText, statusStyle.text]}>
            {STATUS_LABELS[invoice.status] ?? invoice.status}
          </Text>
        </View>
      </View>
    </TouchableOpacity>
  );
}

function formatDate(iso: string): string {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default memo(InvoiceCard);

const styles = StyleSheet.create({
  card: {
    backgroundColor: Colors.white,
    borderRadius: 10,
    padding: 14,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 3,
    elevation: 1,
  },
  cardOverdue: {
    borderLeftWidth: 3,
    borderLeftColor: Colors.danger,
  },
  left: {
    flex: 1,
    gap: 2,
  },
  number: {
    fontSize: 14,
    fontWeight: '700',
    color: Colors.textDark,
    fontFamily: 'monospace',
  },
  date: {
    fontSize: 12,
    color: Colors.textMuted,
  },
  due: {
    fontSize: 12,
    color: Colors.textMuted,
  },
  dueOverdue: {
    color: Colors.danger,
    fontWeight: '600',
  },
  right: {
    alignItems: 'flex-end',
    gap: 6,
  },
  total: {
    fontSize: 15,
    fontWeight: '700',
    color: Colors.textDark,
  },
  badge: {
    borderRadius: 12,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  badgeText: {
    fontSize: 12,
  },
});
