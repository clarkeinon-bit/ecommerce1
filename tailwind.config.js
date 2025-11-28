/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    'node_modules/preline/dist/*.js'
  ],
  theme: {
    extend: {},
  },
  plugins: [
    // Revert to the CJS style that Tailwind's loader often prefers
    require('preline/plugin'), 
    
    // Add the forms plugin back
    require('@tailwindcss/forms'),
  ],
}