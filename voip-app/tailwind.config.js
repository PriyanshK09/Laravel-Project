/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        'encryption': {
          'symmetric': {
            'light': '#c7f0db',
            'DEFAULT': '#0d6832',
            'dark': '#054d22'
          },
          'asymmetric': {
            'light': '#bfdbfe',
            'DEFAULT': '#1e40af',
            'dark': '#1e3a8a'
          }
        },
        'call-status': {
          'initiated': '#fef3c7',
          'connected': '#c7f0db',
          'completed': '#e5e7eb',
          'failed': '#fee2e2'
        }
      },
      animation: {
        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'bounce-slow': 'bounce 2s infinite'
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}