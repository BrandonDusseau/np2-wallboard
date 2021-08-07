const { parallel, src, dest, series } = require('gulp');
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');
const sass = require('gulp-sass')(require('sass'));
const composer = require('gulp-composer');
const gulpif = require('gulp-if');
const del = require('del');

function clean() {
  return del('build/*');
}

function minifyJs() {
  return src(['src/js/*.js', '!src/js/jquery.min.js'])
    .pipe(uglify())
    .pipe(rename({ extname: '.min.js' }))
    .pipe(src('src/js/jquery.min.js'))
    .pipe(dest('build/js/'));
}

function isScssFile(file) {
  return file.extname == '.scss';
}

function css() {
  return src(['src/scss/*.scss', 'src/scss/*.css'])
    .pipe(
      gulpif(
        isScssFile,
        sass({outputStyle: 'compressed'}).on('error', sass.logError)
      )
    )
    .pipe(dest('build/css/'));
}

function phpComposer() {
  composer({ 'working-dir': 'src/', async: false });
  return src('src/vendor/**/*', { base: 'src' })
    .pipe(dest('build/'))
}

function static() {
  return src(
      ['src/data/**/*', 'src/font/**/*', 'src/img/**/*', 'src/.htaccess', 'src/*.php', 'src/*.ini'],
      { base: 'src', dot: true }
    )
    .pipe(dest('build/'));
}

function clearCache() {
  return del("build/data/*.dat");
}

exports.build = parallel(minifyJs, css, phpComposer, static);
exports.clean = clean;
exports.default = series(clean, parallel(minifyJs, css, phpComposer, static));
exports.clearcache = clearCache;
