/**
 * Navigation — React Navigation native-stack
 *
 * Deux piles :
 *   AuthStack  : Login (public)
 *   AppStack   : Clients, ClientDetail, AddClient, EditClient, Projects
 */

import React from 'react';
import { ActivityIndicator, View } from 'react-native';
import { NavigationContainer }     from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';

import { useAuth } from '@/contexts/AuthContext';
import { Colors }  from '@/constants/colors';

// Screens
import LoginScreen        from '@/screens/LoginScreen';
import ClientsScreen      from '@/screens/ClientsScreen';
import ClientDetailScreen from '@/screens/ClientDetailScreen';
import AddClientScreen    from '@/screens/AddClientScreen';
import EditClientScreen   from '@/screens/EditClientScreen';
import ProjectsScreen     from '@/screens/ProjectsScreen';

// ---- Types de navigation (param lists) ----

export type AuthStackParamList = {
  Login: undefined;
};

export type AppStackParamList = {
  Clients:      undefined;
  ClientDetail: { clientId: number };
  AddClient:    undefined;
  EditClient:   { clientId: number };
  Projects:     { clientId?: number };
};

const AuthStack = createNativeStackNavigator<AuthStackParamList>();
const AppStack  = createNativeStackNavigator<AppStackParamList>();

// ---- Pile non authentifiée ----
function AuthNavigator() {
  return (
    <AuthStack.Navigator screenOptions={{ headerShown: false }}>
      <AuthStack.Screen name="Login" component={LoginScreen} />
    </AuthStack.Navigator>
  );
}

// ---- Pile authentifiée ----
function AppNavigator() {
  return (
    <AppStack.Navigator
      screenOptions={{
        headerStyle:       { backgroundColor: Colors.primary },
        headerTintColor:   Colors.white,
        headerTitleStyle:  { fontWeight: '700' },
        headerBackTitle:   'Retour',
      }}
    >
      <AppStack.Screen
        name="Clients"
        component={ClientsScreen}
        options={{ title: 'Clients' }}
      />
      <AppStack.Screen
        name="ClientDetail"
        component={ClientDetailScreen}
        options={{ title: 'Détail client' }}
      />
      <AppStack.Screen
        name="AddClient"
        component={AddClientScreen}
        options={{ title: 'Nouveau client', presentation: 'modal' }}
      />
      <AppStack.Screen
        name="EditClient"
        component={EditClientScreen}
        options={{ title: 'Modifier client', presentation: 'modal' }}
      />
      <AppStack.Screen
        name="Projects"
        component={ProjectsScreen}
        options={{ title: 'Projets' }}
      />
    </AppStack.Navigator>
  );
}

// ---- Root Navigator ----
export default function RootNavigator() {
  const { isAuthenticated: authed, isLoading: loading } = useAuth();

  if (loading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator size="large" color={Colors.primary} />
      </View>
    );
  }

  return (
    <NavigationContainer>
      {authed ? <AppNavigator /> : <AuthNavigator />}
    </NavigationContainer>
  );
}
