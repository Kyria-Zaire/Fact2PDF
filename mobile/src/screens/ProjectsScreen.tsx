/**
 * ProjectsScreen — list of projects, optionally filtered by client
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';

import { ProjectsApi, ApiProject } from '@/services/api';
import ProjectCard                 from '@/components/ProjectCard';
import { Colors }                  from '@/constants/colors';
import { AppStackParamList }       from '@/navigation';

type Props = NativeStackScreenProps<AppStackParamList, 'Projects'>;

export default function ProjectsScreen({ route, navigation }: Props) {
  const clientId = route.params?.clientId;

  const [projects,   setProjects]   = useState<ApiProject[]>([]);
  const [loading,    setLoading]    = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error,      setError]      = useState<string | null>(null);

  async function load(isRefresh = false) {
    try {
      const data = clientId
        ? await ProjectsApi.byClient(clientId)
        : await ProjectsApi.list();
      const raw = Array.isArray(data) ? data : (data as any)?.data ?? data;
      const items: ApiProject[] = Array.isArray(raw) ? raw : [];
      setProjects(items);
      setError(null);
    } catch (err: any) {
      setError(err?.message ?? 'Erreur réseau');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }

  useEffect(() => {
    navigation.setOptions({ title: clientId ? 'Projets client' : 'Projets' });
    load();
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [clientId]);

  const onRefresh = useCallback(() => {
    setRefreshing(true);
    load(true);
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [clientId]);

  const renderItem = useCallback(({ item }: { item: ApiProject }) => (
    <ProjectCard project={item} />
  ), []);

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color={Colors.primary} />
      </View>
    );
  }

  if (error) {
    return (
      <View style={styles.center}>
        <Text style={styles.errorText}>{error}</Text>
      </View>
    );
  }

  return (
    <FlatList
      data={projects}
      keyExtractor={item => String(item.id)}
      renderItem={renderItem}
      contentContainerStyle={styles.list}
      ItemSeparatorComponent={() => <View style={{ height: 10 }} />}
      ListEmptyComponent={
        <View style={styles.empty}>
          <Text style={styles.emptyText}>Aucun projet.</Text>
        </View>
      }
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={Colors.primary} />
      }
    />
  );
}

const styles = StyleSheet.create({
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: Colors.background,
  },
  errorText: {
    color: Colors.danger,
    fontSize: 14,
  },
  list: {
    padding: 12,
    paddingBottom: 30,
  },
  empty: {
    paddingTop: 60,
    alignItems: 'center',
  },
  emptyText: {
    color: Colors.textMuted,
    fontSize: 14,
  },
});
