/**
 * EditClientScreen — edit existing client (pre-filled form)
 */

import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  TextInput,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Image,
  Alert,
  ActivityIndicator,
  Platform,
} from 'react-native';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import * as ImagePicker            from 'expo-image-picker';

import { ClientsApi, ApiClient } from '@/services/api';
import { Colors }                from '@/constants/colors';
import { getAssetUrl }           from '@/constants/config';
import { AppStackParamList }     from '@/navigation';

type Props = NativeStackScreenProps<AppStackParamList, 'EditClient'>;

export default function EditClientScreen({ route, navigation }: Props) {
  const { clientId } = route.params;

  const [client,  setClient]  = useState<ApiClient | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving,  setSaving]  = useState(false);
  const [logoUri, setLogoUri] = useState<string | null>(null);
  const [errors,  setErrors]  = useState<Record<string, string>>({});

  // Form state
  const [name,       setName]       = useState('');
  const [email,      setEmail]      = useState('');
  const [phone,      setPhone]      = useState('');
  const [address,    setAddress]    = useState('');
  const [city,       setCity]       = useState('');
  const [postalCode, setPostalCode] = useState('');
  const [country,    setCountry]    = useState('FR');
  const [notes,      setNotes]      = useState('');

  useEffect(() => {
    ClientsApi.get(clientId)
      .then(data => {
        setClient(data);
        setName(data.name ?? '');
        setEmail(data.email ?? '');
        setPhone(data.phone ?? '');
        setAddress(data.address ?? '');
        setCity(data.city ?? '');
        setPostalCode(data.postal_code ?? '');
        setCountry(data.country ?? 'FR');
        setNotes(data.notes ?? '');
        navigation.setOptions({ title: `Modifier — ${data.name}` });
      })
      .catch(() => Alert.alert('Erreur', 'Client introuvable.'))
      .finally(() => setLoading(false));
  }, [clientId]);

  async function pickPhoto(useCamera: boolean) {
    const perm = useCamera
      ? await ImagePicker.requestCameraPermissionsAsync()
      : await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!perm.granted) {
      Alert.alert('Permission refusée', 'Autorisez l\'accès dans les réglages.');
      return;
    }
    const result = useCamera
      ? await ImagePicker.launchCameraAsync({ allowsEditing: true, aspect: [3, 2], quality: 0.8 })
      : await ImagePicker.launchImageLibraryAsync({ allowsEditing: true, aspect: [3, 2], quality: 0.8 });
    if (!result.canceled) setLogoUri(result.assets[0].uri);
  }

  function showPhotoPicker() {
    Alert.alert('Logo', 'Choisir la source', [
      { text: 'Appareil photo', onPress: () => pickPhoto(true) },
      { text: 'Galerie',        onPress: () => pickPhoto(false) },
      { text: 'Annuler', style: 'cancel' },
    ]);
  }

  function validate(): boolean {
    const errs: Record<string, string> = {};
    if (!name.trim()) errs.name = 'Le nom est requis.';
    if (email && !/\S+@\S+\.\S+/.test(email)) errs.email = 'Email invalide.';
    setErrors(errs);
    return Object.keys(errs ?? {}).length === 0;
  }

  async function handleSave() {
    if (!validate()) return;
    setSaving(true);
    try {
      const payload = { name, email, phone, address, city, postal_code: postalCode, country, notes };
      if (logoUri) {
        await ClientsApi.updateWithLogo(clientId, payload, logoUri);
      } else {
        await ClientsApi.update(clientId, payload);
      }
      navigation.goBack();
    } catch (err: any) {
      Alert.alert('Erreur', err?.message ?? 'Impossible de sauvegarder.');
    } finally {
      setSaving(false);
    }
  }

  if (loading) {
    return (
      <View style={styles.loadingCenter}>
        <ActivityIndicator size="large" color={Colors.primary} />
      </View>
    );
  }

  const currentLogo = logoUri ?? (client?.logo_path ? getAssetUrl(client.logo_path) : null) ?? null;

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
      {/* Logo picker */}
      <TouchableOpacity style={styles.logoPicker} onPress={showPhotoPicker} activeOpacity={0.8}>
        {currentLogo ? (
          <Image source={{ uri: currentLogo }} style={styles.logoPreview} resizeMode="contain" />
        ) : (
          <View style={styles.logoPlaceholder}>
            <Text style={styles.logoPlaceholderText}>📷 Modifier le logo</Text>
          </View>
        )}
      </TouchableOpacity>

      <FieldRow label="Nom *" error={errors.name}>
        <TextInput
          style={[styles.input, errors.name && styles.inputError]}
          value={name} onChangeText={v => { setName(v); if (errors.name) setErrors(e => ({ ...(e ?? {}), name: '' })); }}
        />
      </FieldRow>

      <FieldRow label="Email" error={errors.email}>
        <TextInput
          style={[styles.input, errors.email && styles.inputError]}
          value={email} onChangeText={v => { setEmail(v); if (errors.email) setErrors(e => ({ ...e, email: '' })); }}
          keyboardType="email-address" autoCapitalize="none"
        />
      </FieldRow>

      <FieldRow label="Téléphone">
        <TextInput style={styles.input} value={phone} onChangeText={setPhone} keyboardType="phone-pad" />
      </FieldRow>

      <FieldRow label="Adresse">
        <TextInput style={styles.input} value={address} onChangeText={setAddress} />
      </FieldRow>

      <View style={styles.row}>
        <View style={{ flex: 2 }}>
          <FieldRow label="Ville">
            <TextInput style={styles.input} value={city} onChangeText={setCity} />
          </FieldRow>
        </View>
        <View style={{ flex: 1, marginLeft: 10 }}>
          <FieldRow label="CP">
            <TextInput style={styles.input} value={postalCode} onChangeText={setPostalCode} keyboardType="numeric" />
          </FieldRow>
        </View>
      </View>

      <FieldRow label="Pays">
        <View style={styles.countryRow}>
          {['FR', 'BE', 'CH', 'LU'].map(c => (
            <TouchableOpacity
              key={c}
              style={[styles.chip, country === c && styles.chipActive]}
              onPress={() => setCountry(c)}
            >
              <Text style={[styles.chipText, country === c && styles.chipTextActive]}>{c}</Text>
            </TouchableOpacity>
          ))}
        </View>
      </FieldRow>

      <FieldRow label="Notes">
        <TextInput
          style={[styles.input, { height: 80, textAlignVertical: 'top' }]}
          value={notes} onChangeText={setNotes} multiline numberOfLines={3}
        />
      </FieldRow>

      <TouchableOpacity
        style={[styles.saveBtn, saving && styles.saveBtnDisabled]}
        onPress={handleSave}
        disabled={saving}
        activeOpacity={0.85}
      >
        {saving
          ? <ActivityIndicator color={Colors.white} />
          : <Text style={styles.saveBtnText}>Enregistrer</Text>
        }
      </TouchableOpacity>
    </ScrollView>
  );
}

function FieldRow({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
  return (
    <View style={{ marginBottom: 14 }}>
      <Text style={fr.label}>{label}</Text>
      {children}
      {!!error && <Text style={fr.error}>{error}</Text>}
    </View>
  );
}

const fr = StyleSheet.create({
  label: { fontSize: 13, fontWeight: '600', color: Colors.textDark, marginBottom: 5 },
  error: { fontSize: 12, color: Colors.danger, marginTop: 4 },
});

const styles = StyleSheet.create({
  container:    { flex: 1, backgroundColor: Colors.background },
  content:      { padding: 16, paddingBottom: 40 },
  loadingCenter:{ flex: 1, justifyContent: 'center', alignItems: 'center' },
  logoPicker:   { alignSelf: 'center', marginBottom: 20 },
  logoPreview:  { width: 120, height: 80, borderRadius: 10, backgroundColor: Colors.background, borderWidth: 1, borderColor: Colors.border },
  logoPlaceholder: {
    width: 120, height: 80, borderRadius: 10, backgroundColor: Colors.white,
    borderWidth: 2, borderColor: Colors.border, borderStyle: 'dashed',
    justifyContent: 'center', alignItems: 'center',
  },
  logoPlaceholderText: { fontSize: 13, color: Colors.textMuted, textAlign: 'center' },
  input: {
    backgroundColor: Colors.white, borderWidth: 1, borderColor: Colors.border,
    borderRadius: 8, paddingHorizontal: 12,
    paddingVertical: Platform.OS === 'ios' ? 11 : 8,
    fontSize: 14, color: Colors.textDark,
  },
  inputError: { borderColor: Colors.danger },
  row:         { flexDirection: 'row' },
  countryRow:  { flexDirection: 'row', gap: 8 },
  chip:        { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 8, borderWidth: 1, borderColor: Colors.border, backgroundColor: Colors.white },
  chipActive:  { backgroundColor: Colors.primary, borderColor: Colors.primary },
  chipText:    { fontSize: 13, color: Colors.textDark, fontWeight: '600' },
  chipTextActive: { color: Colors.white },
  saveBtn:     { backgroundColor: Colors.primary, borderRadius: 10, paddingVertical: 14, alignItems: 'center', marginTop: 8 },
  saveBtnDisabled: { opacity: 0.6 },
  saveBtnText: { color: Colors.white, fontWeight: '700', fontSize: 16 },
});
