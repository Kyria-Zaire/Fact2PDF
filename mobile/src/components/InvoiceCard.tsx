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

interface Props {
  invoice: ApiInvoice;
  onPress?: (invoice: ApiInvoice) => void;
}

function InvoiceCard({ invoice, onPress }: Props) {
  const statusColor = {
    draft:   Colors.invoiceDraft,
    pending: Colors.invoicePending,
    paid:    Colors.invoicePaid,
    overdue: Colors.invoiceOverdue,
  }[invoice.status] ?? Colors.textMuted;

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
        <View style={[styles.badge, { backgroundColor: statusColor }]}>
          <Text style={styles.badgeText}>
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
    paddingHorizontal: 8,
    paddingVertical: 3,
  },
  badgeText: {
    fontSize: 11,
    color: Colors.white,
    fontWeight: '600',
  },
});
