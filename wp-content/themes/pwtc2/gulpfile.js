'use strict';

let gulp         = require('gulp'),
    concat       = require('gulp-concat'),
    gulp_if      = require('gulp-if'),
    babel        = require('gulp-babel'),
    sass         = require('gulp-sass'),
    autoprefix   = require('gulp-autoprefixer'),
    uglify       = require('gulp-uglify'),
    imagemin     = require('gulp-imagemin'),
    plumber      = require('gulp-plumber'),
    rename       = require('gulp-rename'),
    notify       = require('gulp-notify'),
    watch        = require('gulp-watch'),
    livereload   = require('gulp-livereload'),
    newer        = require('gulp-newer');

let flags = {
   production: false
};
let scripts_src = [
    'node_modules/foundation-sites/dist/js/foundation.js',
    'node_modules/slick-carousel/slick/slick.js',
    'node_modules/@fancyapps/fancybox/dist/jquery.fancybox.js',
    'node_modules/isotope-layout/dist/isotope.pkgd.js',
    'resources/web/scripts/app.js'
];
let scripts_dist = 'resources/web/scripts-min';
let images_src = 'resources/web/images/**/*.{png,jpg,gif,svg}';
let images_dist = 'resources/web/images-min';
let styles_src = [
    'resources/web/scss/**/*.scss'
];
let styles_paths = [
    'node_modules/foundation-sites/scss',
    'node_modules/slick-carousel/slick',
    'node_modules/@fancyapps/fancybox/dist',
    'src/resources/web/scss'
];
let styles_dist = 'resources/web/stylesheets';

let plumberErrorHandler = { errorHandler: notify.onError({
    title: 'Gulp',
    message: 'Error: <%= error.message %>'
})};

gulp.task('production', function () {
    flags.production = true;
});

gulp.task('default', [
    'build',
    'watch'
]);

gulp.task('build', [
    'images',
    'scripts',
    'styles'
]);

gulp.task('images', () => {
    gulp.src(images_src).pipe(plumber(plumberErrorHandler))
        .pipe(rename({ suffix: '.min' }))
        .pipe(newer(images_dist))
        .pipe(imagemin({
            optimizationLevel:  7,
            progressive:        true,
            interlaced:         true,
            multipass:          true
        }))
        .pipe(gulp.dest(images_dist))
        .pipe(livereload());
});

gulp.task('scripts', () => {
    gulp.src(scripts_src)
        .pipe(concat('app.js'))
        .pipe(plumber(plumberErrorHandler))
        .pipe(babel({
            presets: ['env']
        }))
        .pipe(gulp_if(flags.production, uglify()))
        .pipe(gulp.dest(scripts_dist))
        .pipe(livereload());
});

gulp.task('styles', () => {
    let sass_config = {
        includePaths:   styles_paths,
        outputStyle:    "nested",
        comments:       true,
        sourceComments: true
    };

    if(flags.production) {
        sass_config.outputStyle    = "compressed";
        sass_config.comments       = false;
        sass_config.sourceComments = false;
    }

    gulp.src(styles_src)
        .pipe(plumber(plumberErrorHandler))
        .pipe(sass(sass_config))
        .pipe(autoprefix('last 4 version'))
        .pipe(gulp.dest(styles_dist))
        .pipe(livereload());
});

gulp.task('watch', () => {
    livereload.listen();
    gulp.watch(images_src, ['images']);
    gulp.watch(scripts_src, ['scripts']);
    gulp.watch(styles_src, ['styles']);
});
