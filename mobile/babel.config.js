module.exports = function (api) {
  api.cache(true);
  return {
    presets: ['babel-preset-expo'],
    plugins: [
      // Alias @/ → src/
      ['module-resolver', {
        root: ['./src'],
        alias: { '@': './src' },
        extensions: ['.ios.js', '.android.js', '.js', '.ts', '.tsx', '.json'],
      }],
    ],
  };
};
