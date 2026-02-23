/**
 * ClientsScreen — paginated client list with live search
 */

import React, { useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TextInput,
  StyleSheet,
  ActivityIndicator,
  TouchableOpacity,
  Platform,
} from 'react-native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';

import { useClients }           from '@/hooks/useClients';
import ClientCard               from '@/components/ClientCard';
import { ApiClient }            from '@/services/api';
import { Colors }               from '@/constants/colors';
import { AppStackParamList }    from '@/navigation';

type Props = NativeStackScreenProps<AppStackParamList, 'Clients'>;

export default function ClientsScreen({ navigation }: Props) {
  const { clients, loading, refreshing, hasMore, error, loadMore, refresh } = useClients();
  const [search, setSearch] = useState('');

  const filtered = search.trim()
    ? clients.filter(c =>
        c.name.toLowerCase().includes(search.toLowerCase()) ||
        (c.email ?? '').toLowerCase().includes(search.toLowerCase())
      )
    : clients;

  const handlePress = useCallback((client: ApiClient) => {
    navigation.navigate('ClientDetail', { clientId: client.id });
  }, [navigation]);

  const renderItem = useCallback(({ item }: { item: ApiClient }) => (
    <ClientCard client={item} onPress={handlePress} />
  ), [handlePress]);

  const renderFooter = () => {
    if (!hasMore || filtered.length === 0) return null;
    return (
      <View style={styles.footer}>
        <ActivityIndicator color={Colors.primary} />
      </View>
    );
  };

  const renderEmpty = () => {
    if (loading) return null;
    return (
      <View style={styles.empty}>
        <Text style={styles.emptyText}>
          {error ?? (search ? 'Aucun résultat.' : 'Aucun client.')}
        </Text>
      </View>
    );
  };

  return (
    <View style={styles.container}>
      {/* Search bar */}
      <View style={styles.searchRow}>
        <TextInput
          style={styles.searchInput}
          value={search}
          onChangeText={setSearch}
          placeholder="Rechercher un client…"
          placeholderTextColor={Colors.textMuted}
          clearButtonMode="while-editing"
          returnKeyType="search"
        />
      </View>

      {loading && clients.length === 0 ? (
        <View style={styles.loadingCenter}>
          <ActivityIndicator size="large" color={Colors.primary} />
        </View>
      ) : (
        <FlatList
          data={filtered}
          keyExtractor={item => String(item.id)}
          renderItem={renderItem}
          contentContainerStyle={styles.list}
          ItemSeparatorComponent={() => <View style={{ height: 10 }} />}
          ListFooterComponent={renderFooter}
          ListEmptyComponent={renderEmpty}
          refreshing={refreshing}
          onRefresh={refresh}
          onEndReached={loadMore}
          onEndReachedThreshold={0.3}
          removeClippedSubviews={Platform.OS === 'android'}
        />
      )}

      {/* FAB — Add client */}
      <TouchableOpacity
        style={styles.fab}
        onPress={() => navigation.navigate('AddClient')}
        activeOpacity={0.85}
      >
        <Text style={styles.fabText}>＋</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  searchRow: {
    padding: 12,
    paddingBottom: 6,
  },
  searchInput: {
    backgroundColor: Colors.white,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: Colors.border,
    paddingHorizontal: 14,
    paddingVertical: Platform.OS === 'ios' ? 10 : 8,
    fontSize: 14,
    color: Colors.textDark,
  },
  list: {
    padding: 12,
    paddingTop: 6,
    paddingBottom: 90,
  },
  loadingCenter: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  footer: {
    paddingVertical: 20,
    alignItems: 'center',
  },
  empty: {
    paddingTop: 60,
    alignItems: 'center',
  },
  emptyText: {
    color: Colors.textMuted,
    fontSize: 14,
  },
  fab: {
    position: 'absolute',
    bottom: 24,
    right: 20,
    width: 54,
    height: 54,
    borderRadius: 27,
    backgroundColor: Colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: Colors.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 6,
  },
  fabText: {
    color: Colors.white,
    fontSize: 26,
    lineHeight: 28,
    marginTop: -1,
  },
});
