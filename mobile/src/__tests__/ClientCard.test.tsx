/**
 * Tests — ClientCard
 *
 * Couvre : rendu nom/email, avatar initial, badge factures,
 *          badge montant, callback onPress.
 */

import React from 'react';
import { render, fireEvent } from '@testing-library/react-native';

import ClientCard    from '@/components/ClientCard';
import { ApiClient } from '@/services/api';

const baseClient: ApiClient = {
  id:           10,
  name:         'Zebra Ltd',
  email:        'contact@zebra.io',
  phone:        null,
  address:      null,
  city:         null,
  postal_code:  null,
  country:      'FR',
  logo_path:    null,
  notes:        null,
  contacts:     [],
  invoice_count: 5,
  total_billed:  3200,
};

describe('ClientCard', () => {
  it('renders client name', () => {
    const { getByText } = render(<ClientCard client={baseClient} onPress={jest.fn()} />);
    expect(getByText('Zebra Ltd')).toBeTruthy();
  });

  it('renders email', () => {
    const { getByText } = render(<ClientCard client={baseClient} onPress={jest.fn()} />);
    expect(getByText('contact@zebra.io')).toBeTruthy();
  });

  it('renders initial avatar when no logo', () => {
    const { getByText } = render(<ClientCard client={baseClient} onPress={jest.fn()} />);
    expect(getByText('Z')).toBeTruthy();
  });

  it('renders invoice count badge', () => {
    const { getByText } = render(<ClientCard client={baseClient} onPress={jest.fn()} />);
    expect(getByText('5 factures')).toBeTruthy();
  });

  it('renders total billed badge when > 0', () => {
    const { getByText } = render(<ClientCard client={baseClient} onPress={jest.fn()} />);
    expect(getByText(/3\s*200/)).toBeTruthy(); // "3 200,00 €"
  });

  it('does not render total badge when total_billed is 0', () => {
    const { queryByText } = render(
      <ClientCard client={{ ...baseClient, total_billed: 0 }} onPress={jest.fn()} />
    );
    expect(queryByText(/€/)).toBeNull();
  });

  it('calls onPress with the client when pressed', () => {
    const onPress = jest.fn();
    const { getByText } = render(<ClientCard client={baseClient} onPress={onPress} />);
    fireEvent.press(getByText('Zebra Ltd'));
    expect(onPress).toHaveBeenCalledWith(baseClient);
  });

  it('renders Image when logo_path is set', () => {
    const { getByRole } = render(
      <ClientCard
        client={{ ...baseClient, logo_path: 'https://example.com/logo.png' }}
        onPress={jest.fn()}
      />
    );
    // Image component renders with role "image"
    expect(getByRole('image')).toBeTruthy();
  });

  it('renders singular "facture" when count is 1', () => {
    const { getByText } = render(
      <ClientCard client={{ ...baseClient, invoice_count: 1 }} onPress={jest.fn()} />
    );
    expect(getByText('1 facture')).toBeTruthy();
  });
});
