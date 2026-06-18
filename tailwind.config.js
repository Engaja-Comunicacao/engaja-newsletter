/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './public/**/*.php',
        './public/assets/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                'cinza': '#1E1E1F',
            },
        },
    },
    plugins: [],
}
