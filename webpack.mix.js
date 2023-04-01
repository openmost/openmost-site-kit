let mix = require('laravel-mix');

mix
    .sass('src/scss/app.scss', 'css')
    .js('src/js/app.js', 'js')
    .js('src/js/echarts.js', 'js')
    .vue()
    .setPublicPath('admin');