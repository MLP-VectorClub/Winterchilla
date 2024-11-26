import js from '@eslint/js';
import react from 'eslint-plugin-react';
import globals from 'globals';
import parser from '@babel/eslint-parser';

export default [
  js.configs.recommended,
  react.configs.flat.recommended,
  {
    languageOptions: {
      parser,
      globals: {
        window: true,
        setTimeout: true,
        clearTimeout: true,
        ...globals.jquery,
        ...globals.devtools,
        ...Object.keys(globals.browser).reduce((keys, key) => ({...keys, [key.trim()]: globals.browser[key] }), {}),
        '$body': true,
        '$content': true,
        '$d': true,
        '$footer': true,
        '$head': true,
        '$header': true,
        '$main': true,
        '$navbar': true,
        '$sbToggle': true,
        '$sidebar': true,
        '$w': true,
        'CodeMirror': true,
        'Key': true,
        'PropTypes': true,
        'React': true,
        'ReactDOM': true,
        'Sortable': true,
        'Time': true,
        'ace': true,
        'cuid': true,
        'io': true,
        'jQuery': true,
        'md5': true,
        'mk': true,
        'moment': true,
        'noUiSlider': true,
        'saveAs': true,
      },
      'parserOptions': {
        'sourceType': 'module',
        'ecmaVersion': 6,
        'ecmaFeatures': {
          'jsx': true,
        },
      },
    },
    plugins: {
      react,
    },
    'rules': {
      'camelcase': 0,
      'curly': 0,
      'wrap-iife': [
        2,
        'any',
      ],
      'linebreak-style': 2,
      'comma-style': [
        2,
        'last',
      ],
      'new-cap': 2,
      'strict': 0,
      'no-undef': 2,
      'no-unused-vars': 0,
      'no-console': 0,
      'react/prop-types': 0,
    },
    ignores: [
      '**/lib/',
      '**/public/js/',
    ],
  },
];
