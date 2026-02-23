/**
 * Schémas Realm — chargés uniquement après require('realm').
 * Ce fichier n'importe pas 'realm', pour permettre Expo Go (sans module natif).
 */

function getSchema(Realm) {
  const RealmContact = class RealmContact extends Realm.Object {
    static schema = {
      name: 'Contact',
      primaryKey: '_id',
      properties: {
        _id: 'int',
        client_id: 'int',
        name: 'string',
        email: 'string?',
        phone: 'string?',
        role: 'string?',
        is_primary: 'bool',
      },
    };
  };

  const RealmClient = class RealmClient extends Realm.Object {
    static schema = {
      name: 'Client',
      primaryKey: '_id',
      properties: {
        _id: 'int',
        name: 'string',
        email: 'string?',
        phone: 'string?',
        address: 'string?',
        city: 'string?',
        postal_code: 'string?',
        country: { type: 'string', default: 'FR' },
        logo_path: 'string?',
        notes: 'string?',
        synced_at: { type: 'int', default: 0 },
      },
    };
  };

  const RealmInvoice = class RealmInvoice extends Realm.Object {
    static schema = {
      name: 'Invoice',
      primaryKey: '_id',
      properties: {
        _id: 'int',
        client_id: 'int',
        number: 'string',
        status: 'string',
        issue_date: 'string',
        due_date: 'string',
        subtotal: 'double',
        tax_rate: 'double',
        tax_amount: 'double',
        total: 'double',
        notes: 'string?',
        synced_at: { type: 'int', default: 0 },
      },
    };
  };

  const RealmProject = class RealmProject extends Realm.Object {
    static schema = {
      name: 'Project',
      primaryKey: '_id',
      properties: {
        _id: 'int',
        client_id: 'int',
        name: 'string',
        description: 'string?',
        status: 'string',
        priority: 'string',
        start_date: 'string?',
        end_date: 'string?',
        timeline_json: { type: 'string', default: '[]' },
        progress: { type: 'int', default: 0 },
        synced_at: { type: 'int', default: 0 },
      },
    };
  };

  return [RealmClient, RealmContact, RealmInvoice, RealmProject];
}

module.exports = { getSchema };
