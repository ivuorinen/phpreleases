const colors = require('tailwindcss/colors');

module.exports = {
  corePlugins: {
      overflow: true
  },
  purge: [
    './resources/**/*.blade.php',
  ],
  darkMode: false, // or 'media' or 'class'
  theme: {
      colors: {
          'gray-50': '#FAFAFA',
          'gray-100': '#F3F4F6',
          'gray-200': '#E4E4E7',
          'gray-500': '#71717A',
          'gray-700': '#2E3036',
          'white' : colors.white,
          'black' : colors.black,
          'indigo' : colors.indigo,
      }
  },
  variants: {
    extend: {},
  },
  plugins: [],
}
