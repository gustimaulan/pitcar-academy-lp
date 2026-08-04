/** @type {import('tailwindcss').Config} */
export default {
  content: ['./src/**/*.{astro,html,js,jsx,md,mdx,svelte,ts,tsx,vue}'],
  theme: {
    extend: {
      colors: {
        primary: 'var(--aw-color-primary, rgb(204 0 0))',
        secondary: 'var(--aw-color-secondary, rgb(170 0 0))',
        accent: 'var(--aw-color-accent, rgb(204 0 0))',
        heading: 'var(--aw-color-text-heading, rgb(0 0 0))',
        default: 'var(--aw-color-text-default, rgb(16 16 16))',
        muted: 'var(--aw-color-text-muted, rgb(16 16 16 / 66%))',
        page: 'var(--aw-color-bg-page, rgb(255 255 255))',
        'brand': {
          50:  '#fff1f1',
          100: '#ffe0e0',
          200: '#ffc7c7',
          300: '#ff9e9e',
          400: '#ff6464',
          500: '#ff2d2d',
          600: '#cc0000',  // Main brand color
          700: '#aa0000',  // Darker brand red (#AA0000)
          800: '#990000',
          900: '#7a0000',
          950: '#440000',
        },
      },
      fontFamily: {
        sans: ["'Inter Variable'", 'ui-sans-serif', 'system-ui', 'sans-serif'],
        heading: ["'Inter Variable'", 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      lineHeight: {
        tighter: '1.15',
      },
      animation: {
        fade: 'fadeInUp 1s both',
        'bounce-subtle': 'bounceSubtle 2s infinite',
        float: 'floatAnim 3s ease-in-out infinite',
      },
      keyframes: {
        fadeInUp: {
          '0%': { opacity: '0', transform: 'translateY(2rem)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        bounceSubtle: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-6px)' },
        },
        floatAnim: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-10px)' },
        },
      },
    },
  },
  plugins: [],
};
