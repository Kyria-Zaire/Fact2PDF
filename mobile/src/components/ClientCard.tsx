/**
 * ClientCard — grid card for client list
 */

import React, { memo } from 'react';
import {
  View,
  Text,
  Image,
  TouchableOpacity,
  StyleSheet,
} from 'react-native';
import { ApiClient } from '@/services/api';
import { Colors }    from '@/constants/colors';

interface Props {
  client:  ApiClient;
  onPress: (client: ApiClient) => void;
}

/** Deterministic pastel color from string */
function colorFromString(str: string): string {
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash);
  }
  const h = Math.abs(hash) % 360;
  return `hsl(${h}, 55%, 55%)`;
}

function ClientCard({ client, onPress }: Props) {
  const initial = (client.name?.[0] ?? '?').toUpperCase();
  const color   = colorFromString(client.name);

  return (
    <TouchableOpacity
      style={styles.card}
      onPress={() => onPress(client)}
      activeOpacity={0.75}
    >
      {/* Logo or initial avatar */}
      {client.logo_path ? (
        <Image
          source={{ uri: client.logo_path }}
          style={styles.logo}
          resizeMode="contain"
        />
      ) : (
        <View style={[styles.avatar, { backgroundColor: color }]}>
          <Text style={styles.avatarText}>{initial}</Text>
        </View>
      )}

      <View style={styles.info}>
        <Text style={styles.name} numberOfLines={1}>{client.name}</Text>
        {!!client.email && (
          <Text style={styles.email} numberOfLines={1}>{client.email}</Text>
        )}

        <View style={styles.badges}>
          <View style={styles.badge}>
            <Text style={styles.badgeText}>
              {client.invoice_count ?? 0} facture{(client.invoice_count ?? 0) !== 1 ? 's' : ''}
            </Text>
          </View>
          {(client.total_billed ?? 0) > 0 && (
            <View style={[styles.badge, styles.badgeSuccess]}>
              <Text style={[styles.badgeText, { color: Colors.white }]}>
                {Number(client.total_billed).toLocaleString('fr-FR', {
                  style: 'currency', currency: 'EUR',
                })}
              </Text>
            </View>
          )}
        </View>
      </View>
    </TouchableOpacity>
  );
}

export default memo(ClientCard);

const styles = StyleSheet.create({
  card: {
    backgroundColor: Colors.white,
    borderRadius: 12,
    padding: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.06,
    shadowRadius: 4,
    elevation: 2,
  },
  logo: {
    width: 44,
    height: 44,
    borderRadius: 8,
    backgroundColor: Colors.background,
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 8,
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarText: {
    color: Colors.white,
    fontWeight: '700',
    fontSize: 18,
  },
  info: {
    flex: 1,
  },
  name: {
    fontSize: 15,
    fontWeight: '600',
    color: Colors.textDark,
    marginBottom: 2,
  },
  email: {
    fontSize: 12,
    color: Colors.textMuted,
    marginBottom: 6,
  },
  badges: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
  },
  badge: {
    backgroundColor: Colors.background,
    borderWidth: 1,
    borderColor: Colors.border,
    borderRadius: 12,
    paddingHorizontal: 8,
    paddingVertical: 2,
  },
  badgeSuccess: {
    backgroundColor: Colors.success,
    borderColor: Colors.success,
  },
  badgeText: {
    fontSize: 11,
    color: Colors.textDark,
    fontWeight: '500',
  },
});
