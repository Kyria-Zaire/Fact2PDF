/**
 * ClientDetailScreen — client info + invoices + projects tabs
 */

import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  TouchableOpacity,
  Image,
  Alert,
} from 'react-native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';

import { ClientsApi, ApiClient }  from '@/services/api';
import { useInvoices }            from '@/hooks/useInvoices';
import InvoiceCard                from '@/components/InvoiceCard';
import ProjectCard                from '@/components/ProjectCard';
import { Colors }                 from '@/constants/colors';
import { getAssetUrl }            from '@/constants/config';
import { AppStackParamList }      from '@/navigation';
import { ProjectsApi, ApiProject } from '@/services/api';

type Props = NativeStackScreenProps<AppStackParamList, 'ClientDetail'>;
type Tab   = 'info' | 'invoices' | 'projects';

export default function ClientDetailScreen({ route, navigation }: Props) {
  const { clientId } = route.params;

  const [client,   setClient]   = useState<ApiClient | null>(null);
  const [loading,  setLoading]  = useState(true);
  const [tab,      setTab]      = useState<Tab>('info');
  const [projects, setProjects] = useState<ApiProject[]>([]);
  const [projLoad, setProjLoad] = useState(false);

  const { invoices = [], loading: invLoad, refresh: refreshInv } = useInvoices(clientId);

  // Fetch client detail
  useEffect(() => {
    ClientsApi.get(clientId)
      .then(data => {
        setClient(data);
        navigation.setOptions({ title: data.name });
      })
      .catch(() => Alert.alert('Erreur', 'Impossible de charger le client.'))
      .finally(() => setLoading(false));
  }, [clientId]);

  // Fetch projects when tab opened
  useEffect(() => {
    if (tab !== 'projects' || (projects ?? []).length > 0) return;
    setProjLoad(true);
    ProjectsApi.byClient(clientId)
      .then(data => {
        const raw = Array.isArray(data) ? data : (data as any)?.data ?? data;
        setProjects(Array.isArray(raw) ? raw : []);
      })
      .catch(() => setProjects([]))
      .finally(() => setProjLoad(false));
  }, [tab, clientId]);

  if (loading) {
    return (
      <View style={styles.loadingCenter}>
        <ActivityIndicator size="large" color={Colors.primary} />
      </View>
    );
  }

  if (!client) return null;

  const initial    = (client.name?.[0] ?? '?').toUpperCase();
  const avatarColor = '#4F46E5';

  return (
    <View style={styles.container}>
      {/* Header card */}
      <View style={styles.headerCard}>
        {client.logo_path ? (
          <Image source={{ uri: getAssetUrl(client.logo_path) ?? '' }} style={styles.logo} resizeMode="contain" />
        ) : (
          <View style={[styles.avatar, { backgroundColor: avatarColor }]}>
            <Text style={styles.avatarText}>{initial}</Text>
          </View>
        )}
        <View style={styles.headerInfo}>
          <Text style={styles.clientName}>{client.name}</Text>
          {!!client.email && <Text style={styles.clientMeta}>{client.email}</Text>}
          {!!client.phone && <Text style={styles.clientMeta}>{client.phone}</Text>}
        </View>
        <TouchableOpacity
          style={styles.editBtn}
          onPress={() => navigation.navigate('EditClient', { clientId })}
        >
          <Text style={styles.editBtnText}>Modifier</Text>
        </TouchableOpacity>
      </View>

      {/* Tab bar */}
      <View style={styles.tabBar}>
        {(['info', 'invoices', 'projects'] as Tab[]).map(t => (
          <TouchableOpacity
            key={t}
            style={[styles.tab, tab === t && styles.tabActive]}
            onPress={() => setTab(t)}
          >
            <Text style={[styles.tabText, tab === t && styles.tabTextActive]}>
              {t === 'info' ? 'Infos' : t === 'invoices' ? `Factures (${(invoices ?? []).length})` : 'Projets'}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {/* Tab content */}
      <ScrollView contentContainerStyle={styles.content}>
        {tab === 'info' && <InfoTab client={client} />}
        {tab === 'invoices' && (
          invLoad && (invoices ?? []).length === 0
            ? <ActivityIndicator color={Colors.primary} style={{ marginTop: 40 }} />
            : (invoices ?? []).length === 0
              ? <Text style={styles.empty}>Aucune facture.</Text>
              : (invoices ?? []).map(inv => (
                  <InvoiceCard key={inv.id} invoice={inv} />
                ))
        )}
        {tab === 'projects' && (
          projLoad
            ? <ActivityIndicator color={Colors.primary} style={{ marginTop: 40 }} />
            : (projects ?? []).length === 0
              ? <Text style={styles.empty}>Aucun projet.</Text>
              : (projects ?? []).map(p => (
                  <ProjectCard
                    key={p.id}
                    project={p}
                    onPress={() => navigation.navigate('Projects', { clientId: p.client_id })}
                  />
                ))
        )}
      </ScrollView>
    </View>
  );
}

function InfoTab({ client }: { client: ApiClient }) {
  const fields: [string, string | null | undefined][] = [
    ['Adresse',     client.address],
    ['Ville',       client.city],
    ['Code postal', client.postal_code],
    ['Pays',        client.country],
    ['Notes',       client.notes],
  ];

  return (
    <View style={styles.infoTab}>
      {fields.filter(([, v]) => !!v).map(([label, value]) => (
        <View key={label} style={styles.infoRow}>
          <Text style={styles.infoLabel}>{label}</Text>
          <Text style={styles.infoValue}>{value}</Text>
        </View>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  loadingCenter: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerCard: {
    backgroundColor: Colors.white,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderBottomWidth: 1,
    borderBottomColor: Colors.border,
  },
  logo: {
    width: 52,
    height: 52,
    borderRadius: 10,
    backgroundColor: Colors.background,
  },
  avatar: {
    width: 52,
    height: 52,
    borderRadius: 10,
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarText: {
    color: Colors.white,
    fontWeight: '800',
    fontSize: 22,
  },
  headerInfo: {
    flex: 1,
    gap: 2,
  },
  clientName: {
    fontSize: 17,
    fontWeight: '700',
    color: Colors.textDark,
  },
  clientMeta: {
    fontSize: 13,
    color: Colors.textMuted,
  },
  editBtn: {
    backgroundColor: Colors.primary,
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 7,
  },
  editBtnText: {
    color: Colors.white,
    fontWeight: '600',
    fontSize: 13,
  },
  tabBar: {
    flexDirection: 'row',
    backgroundColor: Colors.white,
    borderBottomWidth: 1,
    borderBottomColor: Colors.border,
  },
  tab: {
    flex: 1,
    paddingVertical: 12,
    alignItems: 'center',
  },
  tabActive: {
    borderBottomWidth: 2,
    borderBottomColor: Colors.primary,
  },
  tabText: {
    fontSize: 13,
    color: Colors.textMuted,
    fontWeight: '500',
  },
  tabTextActive: {
    color: Colors.primary,
    fontWeight: '700',
  },
  content: {
    padding: 12,
    gap: 10,
    paddingBottom: 40,
  },
  infoTab: {
    backgroundColor: Colors.white,
    borderRadius: 10,
    overflow: 'hidden',
  },
  infoRow: {
    flexDirection: 'row',
    paddingHorizontal: 14,
    paddingVertical: 11,
    borderBottomWidth: 1,
    borderBottomColor: Colors.border,
  },
  infoLabel: {
    width: 110,
    fontSize: 13,
    color: Colors.textMuted,
    fontWeight: '500',
  },
  infoValue: {
    flex: 1,
    fontSize: 13,
    color: Colors.textDark,
  },
  empty: {
    textAlign: 'center',
    color: Colors.textMuted,
    marginTop: 40,
    fontSize: 14,
  },
});
