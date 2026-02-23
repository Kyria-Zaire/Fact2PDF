/**
 * AddClientScreen — create a new client with optional logo photo
 */

import React, { useState } from 'react';
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

import { ClientsApi }        from '@/services/api';
import { Colors }            from '@/constants/colors';
import { AppStackParamList } from '@/navigation';

type Props = NativeStackScreenProps<AppStackParamList, 'AddClient'>;

interface FormState {
  name:        string;
  email:       string;
  phone:       string;
  address:     string;
  city:        string;
  postal_code: string;
  country:     string;
  notes:       string;
}

const INIT: FormState = {
  name: '', email: '', phone: '', address: '',
  city: '', postal_code: '', country: 'FR', notes: '',
};

export default function AddClientScreen({ navigation }: Props) {
  const [form,      setForm]      = useState<FormState>(INIT);
  const [logoUri,   setLogoUri]   = useState<string | null>(null);
  const [loading,   setLoading]   = useState(false);
  const [errors,    setErrors]    = useState<Partial<FormState>>({});

  function field(key: keyof FormState) {
    return {
      value:         form[key],
      onChangeText:  (v: string) => {
        setForm(f => ({ ...(f ?? {}), [key]: v }));
        if (errors[key]) setErrors(e => ({ ...(e ?? {}), [key]: undefined }));
      },
    };
  }

  async function pickPhoto(useCamera: boolean) {
    const perm = useCamera
      ? await ImagePicker.requestCameraPermissionsAsync()
      : await ImagePicker.requestMediaLibraryPermissionsAsync();

    if (!perm.granted) {
      Alert.alert('Permission refusée', 'Autorisez l\'accès dans les réglages.');
      return;
    }

    const result = useCamera
      ? await ImagePicker.launchCameraAsync({
          mediaTypes: ImagePicker.MediaTypeOptions.Images,
          allowsEditing: true,
          aspect: [3, 2],
          quality: 0.8,
        })
      : await ImagePicker.launchImageLibraryAsync({
          mediaTypes: ImagePicker.MediaTypeOptions.Images,
          allowsEditing: true,
          aspect: [3, 2],
          quality: 0.8,
        });

    if (!result.canceled) {
      setLogoUri(result.assets[0].uri);
    }
  }

  function showPhotoPicker() {
    Alert.alert('Logo', 'Choisir la source', [
      { text: 'Appareil photo', onPress: () => pickPhoto(true) },
      { text: 'Galerie',        onPress: () => pickPhoto(false) },
      { text: 'Annuler', style: 'cancel' },
    ]);
  }

  function validate(): boolean {
    const errs: Partial<FormState> = {};
    if (!form.name.trim()) errs.name = 'Le nom est requis.';
    if (form.email && !/\S+@\S+\.\S+/.test(form.email)) errs.email = 'Email invalide.';
    setErrors(errs);
    return Object.keys(errs ?? {}).length === 0;
  }

  async function handleSubmit() {
    if (!validate()) return;
    setLoading(true);
    try {
      if (logoUri) {
        // Multipart upload
        await ClientsApi.createWithLogo({ ...form }, logoUri);
      } else {
        await ClientsApi.create({ ...form });
      }
      navigation.goBack();
    } catch (err: any) {
      Alert.alert('Erreur', err?.message ?? 'Impossible de créer le client.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      keyboardShouldPersistTaps="handled"
    >
      {/* Logo picker */}
      <TouchableOpacity style={styles.logoPicker} onPress={showPhotoPicker} activeOpacity={0.8}>
        {logoUri ? (
          <Image source={{ uri: logoUri }} style={styles.logoPreview} resizeMode="contain" />
        ) : (
          <View style={styles.logoPlaceholder}>
            <Text style={styles.logoPlaceholderText}>📷 Ajouter un logo</Text>
          </View>
        )}
      </TouchableOpacity>

      <Field label="Nom *" error={errors.name}>
        <TextInput style={[styles.input, errors.name && styles.inputError]} {...field('name')} />
      </Field>

      <Field label="Email" error={errors.email}>
        <TextInput
          style={[styles.input, errors.email && styles.inputError]}
          {...field('email')}
          keyboardType="email-address"
          autoCapitalize="none"
        />
      </Field>

      <Field label="Téléphone">
        <TextInput style={styles.input} {...field('phone')} keyboardType="phone-pad" />
      </Field>

      <Field label="Adresse">
        <TextInput style={styles.input} {...field('address')} />
      </Field>

      <View style={styles.row}>
        <View style={{ flex: 2 }}>
          <Field label="Ville">
            <TextInput style={styles.input} {...field('city')} />
          </Field>
        </View>
        <View style={{ flex: 1, marginLeft: 10 }}>
          <Field label="Code postal">
            <TextInput style={styles.input} {...field('postal_code')} keyboardType="numeric" />
          </Field>
        </View>
      </View>

      <Field label="Pays">
        <View style={styles.countryRow}>
          {['FR', 'BE', 'CH', 'LU'].map(c => (
            <TouchableOpacity
              key={c}
              style={[styles.countryChip, form.country === c && styles.countryChipActive]}
              onPress={() => setForm(f => ({ ...(f ?? {}), country: c }))}
            >
              <Text style={[styles.countryChipText, form.country === c && styles.countryChipTextActive]}>
                {c}
              </Text>
            </TouchableOpacity>
          ))}
        </View>
      </Field>

      <Field label="Notes">
        <TextInput
          style={[styles.input, { height: 80, textAlignVertical: 'top' }]}
          {...field('notes')}
          multiline
          numberOfLines={3}
        />
      </Field>

      <TouchableOpacity
        style={[styles.submitBtn, loading && styles.submitBtnDisabled]}
        onPress={handleSubmit}
        disabled={loading}
        activeOpacity={0.85}
      >
        {loading
          ? <ActivityIndicator color={Colors.white} />
          : <Text style={styles.submitBtnText}>Créer le client</Text>
        }
      </TouchableOpacity>
    </ScrollView>
  );
}

function Field({ label, error, children }: {
  label: string; error?: string; children: React.ReactNode;
}) {
  return (
    <View style={{ marginBottom: 14 }}>
      <Text style={fieldStyles.label}>{label}</Text>
      {children}
      {!!error && <Text style={fieldStyles.error}>{error}</Text>}
    </View>
  );
}

const fieldStyles = StyleSheet.create({
  label: { fontSize: 13, fontWeight: '600', color: Colors.textDark, marginBottom: 5 },
  error: { fontSize: 12, color: Colors.danger, marginTop: 4 },
});

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  content: {
    padding: 16,
    paddingBottom: 40,
  },
  logoPicker: {
    alignSelf: 'center',
    marginBottom: 20,
  },
  logoPreview: {
    width: 120,
    height: 80,
    borderRadius: 10,
    backgroundColor: Colors.background,
    borderWidth: 1,
    borderColor: Colors.border,
  },
  logoPlaceholder: {
    width: 120,
    height: 80,
    borderRadius: 10,
    backgroundColor: Colors.white,
    borderWidth: 2,
    borderColor: Colors.border,
    borderStyle: 'dashed',
    justifyContent: 'center',
    alignItems: 'center',
  },
  logoPlaceholderText: {
    fontSize: 13,
    color: Colors.textMuted,
    textAlign: 'center',
  },
  input: {
    backgroundColor: Colors.white,
    borderWidth: 1,
    borderColor: Colors.border,
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: Platform.OS === 'ios' ? 11 : 8,
    fontSize: 14,
    color: Colors.textDark,
  },
  inputError: {
    borderColor: Colors.danger,
  },
  row: {
    flexDirection: 'row',
  },
  countryRow: {
    flexDirection: 'row',
    gap: 8,
  },
  countryChip: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: Colors.border,
    backgroundColor: Colors.white,
  },
  countryChipActive: {
    backgroundColor: Colors.primary,
    borderColor: Colors.primary,
  },
  countryChipText: {
    fontSize: 13,
    color: Colors.textDark,
    fontWeight: '600',
  },
  countryChipTextActive: {
    color: Colors.white,
  },
  submitBtn: {
    backgroundColor: Colors.primary,
    borderRadius: 10,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 8,
  },
  submitBtnDisabled: { opacity: 0.6 },
  submitBtnText: {
    color: Colors.white,
    fontWeight: '700',
    fontSize: 16,
  },
});
