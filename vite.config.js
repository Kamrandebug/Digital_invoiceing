import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/assets/js/custom-switcher.js',
                'resources/assets/js/ecommerce-dashboard.js',
                'resources/assets/js/simplebar.js',
                'resources/assets/js/sticky.js',
                'resources/assets/js/main.js',
                'resources/assets/js/defaultmenu.js',
                'resources/assets/js/landing.js'
            ],
            refresh: true,
        }),
        viteStaticCopy({
            targets: [
                {
                    src: 'resources/assets/images',
                    dest: 'assets'
                },
                {
                    src: 'resources/assets/icon-fonts',
                    dest: 'assets'
                },
                {
                    src: 'node_modules/bootstrap/dist/css/bootstrap.min.css',
                    dest: 'assets/libs/bootstrap/css'
                },
                {
                    src: 'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js',
                    dest: 'assets/libs/bootstrap/js'
                },
                {
                    src: 'node_modules/@popperjs/core/dist/umd/popper.min.js',
                    dest: 'assets/libs/@popperjs/core/umd'
                },
                {
                    src: 'node_modules/node-waves/dist/waves.min.css',
                    dest: 'assets/libs/node-waves'
                },
                {
                    src: 'node_modules/node-waves/dist/waves.min.js',
                    dest: 'assets/libs/node-waves'
                },
                {
                    src: 'node_modules/simplebar/dist/simplebar.min.css',
                    dest: 'assets/libs/simplebar'
                },
                {
                    src: 'node_modules/simplebar/dist/simplebar.min.js',
                    dest: 'assets/libs/simplebar'
                },
                {
                    src: 'node_modules/@simonwep/pickr/dist/themes/nano.min.css',
                    dest: 'assets/libs/@simonwep/pickr/themes'
                },
                {
                    src: 'node_modules/@simonwep/pickr/dist/pickr.es5.min.js',
                    dest: 'assets/libs/@simonwep/pickr'
                },
                {
                    src: 'node_modules/flatpickr/dist/flatpickr.min.css',
                    dest: 'assets/libs/flatpickr'
                },
                {
                    src: 'node_modules/flatpickr/dist/flatpickr.min.js',
                    dest: 'assets/libs/flatpickr'
                },
                {
                    src: 'node_modules/@tarekraafat/autocomplete.js/dist/css/autoComplete.css',
                    dest: 'assets/libs/@tarekraafat/autocomplete.js/css'
                },
                {
                    src: 'node_modules/@tarekraafat/autocomplete.js/dist/autoComplete.min.js',
                    dest: 'assets/libs/@tarekraafat/autocomplete.js'
                },
                {
                    src: 'node_modules/choices.js/public/assets/scripts/choices.min.js',
                    dest: 'assets/libs/choices.js/public/assets/scripts'
                },
                {
                    src: 'node_modules/choices.js/public/assets/styles/choices.min.css',
                    dest: 'assets/libs/choices.js/public/assets/styles'
                },
                {
                    src: 'node_modules/apexcharts/dist/apexcharts.min.js',
                    dest: 'assets/libs/apexcharts'
                }
            ]
        })
    ],
});
