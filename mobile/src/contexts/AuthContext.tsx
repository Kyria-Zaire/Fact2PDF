/**
 * AuthContext — État d'authentification partagé pour que le RootNavigator
 * bascule vers l'app après login (sinon il ne re-vérifie qu'au montage).
 */

import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { isAuthenticated } from '@/services/auth';

type AuthContextValue = {
  isAuthenticated: boolean;
  isLoading: boolean;
  refreshAuth: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [loading, setLoading] = useState(true);
  const [authed, setAuthed] = useState(false);

  const refreshAuth = useCallback(async () => {
    const ok = await isAuthenticated();
    setAuthed(ok);
  }, []);

  useEffect(() => {
    isAuthenticated()
      .then(ok => {
        setAuthed(ok);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, []);

  const value: AuthContextValue = {
    isAuthenticated: authed,
    isLoading: loading,
    refreshAuth,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
