/* gulpfile.js */
const { src, dest, parallel, series, watch } = require("gulp");
const autoprefixer = require("autoprefixer");
const sass = require("gulp-sass")(require("sass"));
const sourcemaps = require("gulp-sourcemaps");
const postcss = require("gulp-postcss");
const uswds = require("@uswds/compile");
sass.compiler = require('sass');

/**
 * USWDS version
 */
uswds.settings.version = 3;

/**
 * Path settings
 * Set as many as you need
 */
uswds.paths.dist.theme = './sass/uswds';
uswds.paths.dist.css = './dist/uswds/css';
uswds.paths.dist.fonts = './dist/uswds/fonts';
uswds.paths.dist.img = './dist/uswds/img';
uswds.paths.dist.js = './dist/uswds/js';

let getSrcFrom = (key) => {
  if (uswds.paths.src[key]) {
    return uswds.paths.src[key];
  }
  return uswds.paths.src.defaults[`v${uswds.settings.version}`][key];
};

const buildSettings = {
  plugins: [
    autoprefixer({
      cascade: false,
      grid: true,
      overrideBrowserslist: uswds.settings.compile.browserslist,
    }),
  ],
  includes: [
    'components/**/*.scss',
    getSrcFrom("uswds"),
    getSrcFrom("sass"),
  ],
};

function customSass() {
  return src('./sass/custom/**/*.scss')
    .pipe(sass(
      {
        includePaths: buildSettings.includes
      }
    ).on('error', sass.logError))
    .pipe(postcss([
      autoprefixer(),
    ]))
    .pipe(dest('./dist/custom/css'));
};

function buildComponents() {
  return src('components/**/*.scss')
    .pipe(sourcemaps.init({ largeFile: true }))
    .pipe(
      sass({ includePaths: buildSettings.includes })
    )
    .pipe(postcss(buildSettings.plugins))
    .pipe(sourcemaps.write("."))
    .pipe(dest('components'));
}

/**
 * Exports.
 */
exports.init = series(uswds.copyAssets, uswds.compile, buildComponents, customSass);
exports.compile = parallel(uswds.compile, buildComponents, customSass);
exports.watch = (done) => {
  watch([uswds.paths.dist.theme], series([uswds.compile, buildComponents]));
  watch(['./components/**/*.scss'], buildComponents);
  watch(['./sass/custom/**/*.scss'], customSass);
  done();
};
