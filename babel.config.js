module.exports = {
  presets: [
    [
      '@babel/preset-env',
      {
        modules: false,
      },
    ],
    '@babel/react',
  ],
  env: {
    test: {
      presets: [
        [
          '@babel/preset-env',
        ],
        '@babel/react',
      ],
    },
  },
  plugins: [ '@babel/plugin-proposal-class-properties' ],
};