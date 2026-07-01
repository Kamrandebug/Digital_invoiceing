import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import fs from 'fs';
import path from 'path';

// Helper to get all JS files from a directory
function getFilesFromDir(dir) {
    const directoryPath = path.resolve(__dirname, dir);
    if (!fs.existsSync(directoryPath)) return [];

    return fs.readdirSync(directoryPath)
        .filter(file => file.endsWith('.js'))
        .map(file => `${dir}/${file}`);
}

const assetJsFiles = getFilesFromDir('resources/assets/js');

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/sass/app.scss',
                'resources/js/app.js',
                ...assetJsFiles,
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
                    src: 'resources/assets/web-assets',
                    dest: 'assets'
                },
                {
                    src: 'resources/assets/themecss',
                    dest: 'assets'
                },
                {
                    src: 'resources/assets/js-legacy/select2.js',
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
