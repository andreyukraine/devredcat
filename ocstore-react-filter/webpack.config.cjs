const path = require('path');
const webpack = require('webpack');

module.exports = {
  entry: './src/react-main.js',
  output: {
    filename: 'react-main.js',
    path: path.resolve(__dirname, '../catalog/view/javascript/react'),
    library: 'ReactApp',
    libraryTarget: 'umd'
  },
  module: {
    rules: [
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: [
              ['@babel/preset-env', {
                targets: "defaults",
                useBuiltIns: 'usage', // Оптимизация полифиллов
                corejs: 3
              }],
              '@babel/preset-react'
            ],
            plugins: [
              '@babel/plugin-transform-runtime' // Для асинхронного кода
            ]
          }
        }
      },
      {
        test: /\.css$/,
        use: [
          'style-loader',
          {
            loader: 'css-loader',
            options: {
              modules: false,
              importLoaders: 1
            }
          }
        ]
      },
      {
        test: /\.svg$/,
        use: [
          {
            loader: '@svgr/webpack',
            options: {
              svgoConfig: {
                plugins: [
                  { removeViewBox: false } // Сохраняет viewBox
                ]
              }
            }
          },
          'file-loader'
        ]
      }
    ]
  },
  resolve: {
    extensions: ['.js', '.jsx'], // Автоматическое разрешение расширений
    alias: {
      '@': path.resolve(__dirname, 'src') // Алиас для путей
    }
  },
  plugins: [
    new webpack.DefinePlugin({
      'process.env.NODE_ENV': JSON.stringify('production')
    }),
    new webpack.ProvidePlugin({
      React: 'react' // Автоматическое подключение React
    })
  ],
  mode: 'production',
  performance: {
    hints: false // Отключаем предупреждения о размере бандла
  }
};
