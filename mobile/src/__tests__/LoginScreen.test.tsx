/**
 * Tests — LoginScreen
 *
 * Couvre : rendu du formulaire, validation locale, appel AuthApi,
 *          stockage token, affichage erreur réseau.
 */

import React from 'react';
import { render, fireEvent, waitFor, act } from '@testing-library/react-native';

// --- Mocks ---
jest.mock('@/services/api', () => ({
  AuthApi: {
    login: jest.fn(),
  },
}));

jest.mock('@/services/auth', () => ({
  saveToken: jest.fn().mockResolvedValue(undefined),
  saveUser:  jest.fn().mockResolvedValue(undefined),
}));

// Navigation mock
const mockReset = jest.fn();
const mockGetParent = jest.fn(() => ({ reset: mockReset }));
const mockNavigation = { getParent: mockGetParent } as any;

import LoginScreen from '@/screens/LoginScreen';
import { AuthApi } from '@/services/api';
import { saveToken, saveUser } from '@/services/auth';

const route = {} as any;

// ----------------------------------------------------------

describe('LoginScreen', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders email + password inputs and login button', () => {
    const { getByPlaceholderText, getByText } = render(
      <LoginScreen navigation={mockNavigation} route={route} />
    );
    expect(getByPlaceholderText('admin@example.com')).toBeTruthy();
    expect(getByPlaceholderText('••••••••')).toBeTruthy();
    expect(getByText('Se connecter')).toBeTruthy();
  });

  it('shows validation error when fields are empty', async () => {
    const { getByText } = render(
      <LoginScreen navigation={mockNavigation} route={route} />
    );
    fireEvent.press(getByText('Se connecter'));
    await waitFor(() => {
      expect(getByText('Veuillez remplir tous les champs.')).toBeTruthy();
    });
  });

  it('calls AuthApi.login with trimmed email', async () => {
    (AuthApi.login as jest.Mock).mockResolvedValue({
      token: 'tok123',
      user:  { id: 1, username: 'admin', email: 'admin@example.com', role: 'admin' },
    });

    const { getByPlaceholderText, getByText } = render(
      <LoginScreen navigation={mockNavigation} route={route} />
    );

    fireEvent.changeText(getByPlaceholderText('admin@example.com'), '  admin@example.com  ');
    fireEvent.changeText(getByPlaceholderText('••••••••'), 'password');

    await act(async () => {
      fireEvent.press(getByText('Se connecter'));
    });

    expect(AuthApi.login).toHaveBeenCalledWith('admin@example.com', 'password');
  });

  it('saves token and user on successful login', async () => {
    (AuthApi.login as jest.Mock).mockResolvedValue({
      token: 'tok123',
      user:  { id: 1, username: 'admin', email: 'admin@example.com', role: 'admin' },
    });

    const { getByPlaceholderText, getByText } = render(
      <LoginScreen navigation={mockNavigation} route={route} />
    );
    fireEvent.changeText(getByPlaceholderText('admin@example.com'), 'admin@example.com');
    fireEvent.changeText(getByPlaceholderText('••••••••'), 'password');

    await act(async () => {
      fireEvent.press(getByText('Se connecter'));
    });

    expect(saveToken).toHaveBeenCalledWith('tok123');
    expect(saveUser).toHaveBeenCalled();
  });

  it('displays API error message on failure', async () => {
    (AuthApi.login as jest.Mock).mockRejectedValue({ message: 'Identifiants incorrects.' });

    const { getByPlaceholderText, getByText, findByText } = render(
      <LoginScreen navigation={mockNavigation} route={route} />
    );
    fireEvent.changeText(getByPlaceholderText('admin@example.com'), 'bad@example.com');
    fireEvent.changeText(getByPlaceholderText('••••••••'), 'wrong');

    await act(async () => {
      fireEvent.press(getByText('Se connecter'));
    });

    expect(await findByText('Identifiants incorrects.')).toBeTruthy();
  });
});
