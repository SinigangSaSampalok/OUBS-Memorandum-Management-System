/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // OUBS Theme Colors
        oubs: {
          primary: '#1e40af',
          secondary: '#1d4ed8',
          accent: '#3b82f6',
        },
        // BOR Theme Colors
        bor: {
          primary: '#7c3aed',
          secondary: '#8b5cf6',
          accent: '#a78bfa',
        },
        // Academic Council Colors
        uac: {
          primary: '#1d4ed8',
          secondary: '#2563eb',
          accent: '#3b82f6',
        },
        // Admin Council Colors
        uadmin: {
          primary: '#059669',
          secondary: '#10b981',
          accent: '#34d399',
        }
      }
    },
  },
  plugins: [],
}