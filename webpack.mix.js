let mix = require('laravel-mix');

mix
    .js('src/js/app.js', 'js')
    .vue()
    .setPublicPath('dist');