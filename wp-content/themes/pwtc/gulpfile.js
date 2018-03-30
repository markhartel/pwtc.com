var gulp         = require('gulp'),
    concat       = require('gulp-concat'),
    sass         = require('gulp-sass'),
    autoprefix   = require('gulp-autoprefixer'),
    uglify       = require('gulp-uglify'),
    imagemin     = require('gulp-imagemin'),
    plumber      = require('gulp-plumber'),
    rename       = require('gulp-rename'),
    notify       = require('gulp-notify'),
    watch        = require('gulp-watch'),
    livereload   = require('gulp-livereload'),
    del          = require('del'),
    newer        = require('gulp-newer');

var options = {
    images: {
        src: 'assets/images/**/*.{png,jpg,gif,svg}',
        dist: 'assets/images-min',
        optimizationLevel: 7,
        progressive: true,
        interlaced: true,
        multipass: true
    },
    scripts: {
        src: [
            'assets/libs/foundation-sites/dist/js/foundation.js',
            'assets/libs/slick-carousel/slick/slick.js',
            'assets/libs/fancybox/source/jquery.fancybox.js',
            'assets/scripts/app.js'
        ],
        dist: 'assets/scripts-min'
    },
    styles: {
        src: [
            'assets/scss/**/*.scss'
        ],
        dist: 'assets/styles',
        style: 'compressed',
        includePaths: [
            'assets/libs/slick-carousel/slick',
            'assets/libs/fancybox/source',
            'assets/libs/font-awesome/scss',
            'assets/libs/foundation-sites/scss'
        ],
        sourceComments: true
    }
};

var plumberErrorHandler = { errorHandler: notify.onError({
    title: 'Gulp',
    message: 'Error: <%= error.message %>'
})};

gulp.task('default', [
    'images',
    'scripts',
    'styles',
    'watch'
]);

gulp.task('build', [
    'images',
    'scripts',
    'styles'
]);

gulp.task('clear_cache', function() {
    del(['./cache/**/*']);
});

gulp.task('images', function(){
    gulp.src(options.images.src).pipe(plumber(plumberErrorHandler))
        .pipe(rename({ suffix: '.min' }))
        .pipe(newer(options.images.dist))
        .pipe(imagemin({
            optimizationLevel:  options.images.optimizationLevel,
            progressive:        options.images.progressive,
            interlaced:         options.images.interlaced,
            multipass:          options.images.multipass
        }))
        .pipe(gulp.dest(options.images.dist))
        .pipe(livereload());
});

gulp.task('scripts', function(){
    gulp.src(options.scripts.src)
        .pipe(concat('app.js'))
        .pipe(plumber(plumberErrorHandler))
        .pipe(rename({ suffix: '.min' }))
        //.pipe(uglify())
        .pipe(gulp.dest(options.scripts.dist))
        .pipe(livereload());
});

gulp.task('styles', function(){
    gulp.src(options.styles.src).pipe(plumber(plumberErrorHandler))
        .pipe(sass({
            outputStyle:    options.styles.style,
            includePaths:   options.styles.includePaths,
            comments:       options.styles.comments,
            source_map:     options.styles.source_map,
            time:           options.styles.time
        }))
        .pipe(autoprefix('last 2 version'))
        .pipe(gulp.dest(options.styles.dist))
        .pipe(livereload());
});

gulp.task('watch', function(){
    livereload.listen();
    gulp.watch(options.images.src, ['images']);
    gulp.watch(options.scripts.src, ['scripts']);
    gulp.watch(options.styles.src, ['styles']);
});