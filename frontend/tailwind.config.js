/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['DM Sans', 'system-ui', 'sans-serif'],
        display: ['Fraunces', 'serif'],
      },
      colors: {
        accent: { DEFAULT: '#22d3ee', dim: '#0891b2' },
        surface: '#18181c',
        border: '#2a2a30',
      },
    },
  },
  plugins: [],
};
