import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
// REMOVED: import tailwindcss from '@tailwindcss/vite'; 
// We uninstalled this package, so it must be removed from the imports.

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        // REMOVED: tailwindcss(), 
        // Tailwind is now handled by PostCSS (which Laravel handles by default) 
        // since we are not using the v4 Vite plugin.
    ],
});