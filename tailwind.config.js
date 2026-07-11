import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                bordeaux: {
                    DEFAULT: '#800020',
                    dark: '#5C0017',
                    light: '#A6334D',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                soft: '0 10px 30px -10px rgba(128,0,32,0.25)',
            },
        },
    },

    plugins: [forms],
};
