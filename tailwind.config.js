import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Porrop theme: "steady shop". Deep petrol + saffron on warm sand.
 *
 * Built for how a vendor should feel: relieved, proud, in control, safe.
 *  - Petrol (primary) is the steady colour: dark panels, headings, the "this is solid" feel.
 *  - Saffron (accent) is the "go" colour: the main buttons and the money moments.
 *  - Sand (neutral.bg) is warm and quiet, so nothing shouts.
 *  - WhatsApp green stays reserved for WhatsApp things only.
 *
 * Contrast (checked): ink on saffron ~7:1, petrol on sand ~8:1, white on petrol ~9:1.
 * Saffron is a fill colour. Never use it for small text on sand; use accent-dark.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                // A warm serif: reads as a proper shop sign, not a tech template.
                heading: ['Fraunces', 'Georgia', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                ink: '#0B2E33',
                primary: {
                    light: '#E3F0EF',
                    DEFAULT: '#0F5257',
                    dark: '#0B3A3E',
                },
                secondary: {
                    light: '#FBF8F1',
                    DEFAULT: '#EFE7D6',
                },
                accent: {
                    light: '#FFD98A',
                    DEFAULT: '#F5A524',
                    dark: '#8A5A00',
                },
                whatsapp: '#25D366',
                neutral: {
                    bg: '#F8F4EC',
                    text: '#0B2E33',
                },
            },
            // Soft, layered shadows: calm depth instead of hard outlines.
            boxShadow: {
                soft: '0 1px 2px rgba(11, 46, 51, 0.08), 0 10px 28px -10px rgba(11, 46, 51, 0.22)',
                'soft-lg': '0 2px 4px rgba(11, 46, 51, 0.08), 0 24px 48px -16px rgba(11, 46, 51, 0.30)',
            },
        },
    },
    plugins: [forms],
};
