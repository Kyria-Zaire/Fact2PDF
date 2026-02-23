/**
 * realm.ts — Schémas Realm pour le stockage offline
 *
 * Permet de consulter clients, factures et projets sans connexion.
 * La synchronisation avec l'API se fait via useOfflineSync.
 */

import Realm, { ObjectSchema } from 'realm';

// ============================================================
// Schémas
// ============================================================

export class RealmContact extends Realm.Object<RealmContact> {
  _id!:       number;
  client_id!: number;
  name!:      string;
  email?:     string;
  phone?:     string;
  role?:      string;
  is_primary!:boolean;

  static schema: ObjectSchema = {
    name:       'Contact',
    primaryKey: '_id',
    properties: {
      _id:       'int',
      client_id: 'int',
      name:      'string',
      email:     'string?',
      phone:     'string?',
      role:      'string?',
      is_primary:'bool',
    },
  };
}

export class RealmClient extends Realm.Object<RealmClient> {
  _id!:          number;
  name!:         string;
  email?:        string;
  phone?:        string;
  address?:      string;
  city?:         string;
  postal_code?:  string;
  country!:      string;
  logo_path?:    string;
  notes?:        string;
  /** Timestamp de dernière synchro (ms) */
  synced_at!:    number;

  static schema: ObjectSchema = {
    name:       'Client',
    primaryKey: '_id',
    properties: {
      _id:         'int',
      name:        'string',
      email:       'string?',
      phone:       'string?',
      address:     'string?',
      city:        'string?',
      postal_code: 'string?',
      country:     { type: 'string', default: 'FR' },
      logo_path:   'string?',
      notes:       'string?',
      synced_at:   { type: 'int', default: 0 },
    },
  };
}

export class RealmInvoice extends Realm.Object<RealmInvoice> {
  _id!:        number;
  client_id!:  number;
  number!:     string;
  status!:     string;
  issue_date!: string;
  due_date!:   string;
  subtotal!:   number;
  tax_rate!:   number;
  tax_amount!: number;
  total!:      number;
  notes?:      string;
  synced_at!:  number;

  static schema: ObjectSchema = {
    name:       'Invoice',
    primaryKey: '_id',
    properties: {
      _id:        'int',
      client_id:  'int',
      number:     'string',
      status:     'string',
      issue_date: 'string',
      due_date:   'string',
      subtotal:   'double',
      tax_rate:   'double',
      tax_amount: 'double',
      total:      'double',
      notes:      'string?',
      synced_at:  { type: 'int', default: 0 },
    },
  };
}

export class RealmProject extends Realm.Object<RealmProject> {
  _id!:         number;
  client_id!:   number;
  name!:        string;
  description?: string;
  status!:      string;
  priority!:    string;
  start_date?:  string;
  end_date?:    string;
  /** JSON sérialisé des étapes timeline */
  timeline_json!: string;
  progress!:    number;
  synced_at!:   number;

  static schema: ObjectSchema = {
    name:       'Project',
    primaryKey: '_id',
    properties: {
      _id:           'int',
      client_id:     'int',
      name:          'string',
      description:   'string?',
      status:        'string',
      priority:      'string',
      start_date:    'string?',
      end_date:      'string?',
      timeline_json: { type: 'string', default: '[]' },
      progress:      { type: 'int',    default: 0 },
      synced_at:     { type: 'int',    default: 0 },
    },
  };
}

// ============================================================
// Singleton Realm
// ============================================================

let _realm: Realm | null = null;

export async function getRealmInstance(): Promise<Realm> {
  if (_realm && !_realm.isClosed) return _realm;

  _realm = await Realm.open({
    schema:        [RealmClient, RealmContact, RealmInvoice, RealmProject],
    schemaVersion: 1,
    // Migration vide : schéma v1 initial
    migration:     () => {},
  });

  return _realm;
}

export function closeRealm(): void {
  if (_realm && !_realm.isClosed) {
    _realm.close();
    _realm = null;
  }
}
