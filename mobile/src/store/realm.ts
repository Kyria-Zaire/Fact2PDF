/**
 * realm.ts — Stockage offline avec Realm (chargé à la demande pour Expo Go)
 *
 * En Expo Go, le module natif Realm n'existe pas : getRealmInstance() retourne null
 * et l'app utilise uniquement l'API. Avec un development build, Realm est utilisé.
 */

import type Realm from 'realm';
import Constants from 'expo-constants';

/** Valeur pour upsert (Realm.UpdateMode.Modified) */
export const UPDATE_MODE_MODIFIED = 2;

let _realm: Realm | null = null;
let _realmUnavailable = false;

/** Expo Go ne contient pas le module natif Realm → on ne charge jamais le package */
function isExpoGo(): boolean {
  return Constants.appOwnership === 'expo';
}

/**
 * Retourne l'instance Realm ou null si indisponible (Expo Go).
 * En Expo Go on ne fait jamais require('realm') pour éviter l'erreur native.
 */
export async function getRealmInstance(): Promise<Realm | null> {
  if (isExpoGo()) {
    _realmUnavailable = true;
    return null;
  }
  if (_realmUnavailable) return null;
  if (_realm != null && !_realm.isClosed) return _realm;

  try {
    const RealmModule = require('realm').default;
    const { getSchema } = require('./realmSchema');
    const schema = getSchema(RealmModule);
    _realm = await RealmModule.open({
      schema,
      schemaVersion: 1,
      migration: () => {},
    });
    return _realm;
  } catch {
    _realmUnavailable = true;
    _realm = null;
    return null;
  }
}

export function closeRealm(): void {
  if (_realm != null && !_realm.isClosed) {
    _realm.close();
    _realm = null;
  }
}
