/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    './index.html',
    './src/**/*.{vue,js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      // CSS variable-mapped colours used by shadcn-vue components internally.
      colors: {
        border: 'hsl(var(--border))',
        input: 'hsl(var(--input))',
        ring: 'hsl(var(--ring))',
        background: 'hsl(var(--background))',
        foreground: 'hsl(var(--foreground))',
        primary: {
          DEFAULT: 'hsl(var(--primary))',
          foreground: 'hsl(var(--primary-foreground))',
        },
        secondary: {
          DEFAULT: 'hsl(var(--secondary))',
          foreground: 'hsl(var(--secondary-foreground))',
        },
        destructive: {
          DEFAULT: 'hsl(var(--destructive))',
          foreground: 'hsl(var(--destructive-foreground))',
        },
        muted: {
          DEFAULT: 'hsl(var(--muted))',
          foreground: 'hsl(var(--muted-foreground))',
        },
        accent: {
          DEFAULT: 'hsl(var(--accent))',
          foreground: 'hsl(var(--accent-foreground))',
        },
        // ClassHub design tokens — used directly in page and component classes.
        teal: {
          DEFAULT: '#0B6B6F',
          hover: '#09585C',
          active: '#074649',
          light: '#E0F2F2',
        },
        sidebar: '#042F33',
        'app-bg': '#F8FAFB',
        'card-bg': '#FFFFFF',
        'brand-border': '#E7EDF0',
        'text-primary': '#172B36',
        'text-secondary': '#637381',
        success: {
          bg: '#E9F7EE',
          text: '#2B8A57',
        },
        warning: {
          bg: '#FFF8EE',
          text: '#D9822B',
        },
        danger: {
          bg: '#FDE8E8',
          text: '#D14343',
        },
        purple: {
          bg: '#F2EDFF',
          text: '#6941C6',
          light: '#F2EDFF',
        },
      },
      borderRadius: {
        sm: '8px',
        md: '12px',
        lg: '16px',
        full: '999px',
      },
      boxShadow: {
        card: '0 1px 3px rgba(0,0,0,0.05)',
        'card-hover': '0 4px 12px rgba(0,0,0,0.08)',
      },
    },
  },
  plugins: [],
}
