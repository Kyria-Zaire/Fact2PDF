/**
 * Jest global setup — mocks native modules unavailable in Node.js
 */

// expo-secure-store
jest.mock('expo-secure-store', () => ({
  setItemAsync:    jest.fn().mockResolvedValue(undefined),
  getItemAsync:    jest.fn().mockResolvedValue(null),
  deleteItemAsync: jest.fn().mockResolvedValue(undefined),
}));

// expo-network
jest.mock('expo-network', () => ({
  getNetworkStateAsync: jest.fn().mockResolvedValue({ isConnected: true, isInternetReachable: true }),
}));

// expo-notifications
jest.mock('expo-notifications', () => ({
  requestPermissionsAsync:     jest.fn().mockResolvedValue({ granted: true }),
  getExpoPushTokenAsync:       jest.fn().mockResolvedValue({ data: 'ExpoToken[test]' }),
  setNotificationChannelAsync: jest.fn().mockResolvedValue(undefined),
  scheduleNotificationAsync:   jest.fn().mockResolvedValue(undefined),
  addNotificationReceivedListener: jest.fn(() => ({ remove: jest.fn() })),
  AndroidImportance: { HIGH: 4 },
}));

// expo-image-picker
jest.mock('expo-image-picker', () => ({
  requestCameraPermissionsAsync:        jest.fn().mockResolvedValue({ granted: true }),
  requestMediaLibraryPermissionsAsync:  jest.fn().mockResolvedValue({ granted: true }),
  launchCameraAsync:                    jest.fn().mockResolvedValue({ canceled: true }),
  launchImageLibraryAsync:              jest.fn().mockResolvedValue({ canceled: true }),
  MediaTypeOptions: { Images: 'Images' },
}));

// @react-native-community/netinfo
jest.mock('@react-native-community/netinfo', () => ({
  addEventListener: jest.fn(() => jest.fn()),
}));

// realm
jest.mock('realm', () => {
  class FakeRealm {
    static Object = class {};
    static UpdateMode = { Modified: 'modified' };
    static open = jest.fn().mockResolvedValue(new FakeRealm());
    isClosed = false;
    close = jest.fn();
    write = jest.fn((cb: () => void) => cb());
    create = jest.fn();
    objects = jest.fn(() => ({
      sorted:   jest.fn().mockReturnThis(),
      filtered: jest.fn().mockReturnThis(),
      map:      jest.fn().mockReturnValue([]),
      length:   0,
    }));
  }
  return FakeRealm;
});

// react-native-safe-area-context
jest.mock('react-native-safe-area-context', () => ({
  SafeAreaProvider: ({ children }: any) => children,
  SafeAreaView:     ({ children }: any) => children,
  useSafeAreaInsets: () => ({ top: 0, bottom: 0, left: 0, right: 0 }),
}));
