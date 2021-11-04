'use strict';

var plugins       = require('gulp-load-plugins');
var yargs         = require('yargs');
var gulp          = require('gulp');
var rimraf        = require('rimraf');
var yaml          = require('js-yaml');
var fs            = require('fs');
var named         = require('vinyl-named');
var through2      = require('through2');

// Load all Gulp plugins into one variable
const $ = plugins();

// Check for --production flag
let PRODUCTION = !!(yargs.argv.production);
let VERSION_BUMP = yargs.argv.release;      // Check for --release (x.x.x semver version number)

// Load settings from settings.yml
const { PATHS, LOCAL_PATH } = loadConfig();

// Define default webpack object
let webpackConfig = {
	mode: (PRODUCTION ? 'production' : 'development'),
	externals: {
		jquery: 'jQuery'
	},
	devtool: ! PRODUCTION && 'source-map'
};

/**
 * Load in additional config files
 */
function loadConfig() {
	let ymlFile = fs.readFileSync('config.yml', 'utf8' );
	return yaml.load(ymlFile);
}

/**
 * Set production mode during the build process
 *
 * @param done
 */
function setProductionMode(done) {
	PRODUCTION = false;
	webpackConfig.mode = 'production';
	webpackConfig.devtool = false;

	sassConfig.production = true;
	done();
}

// Build the "dist" folder by running all the below tasks
// Sass must be run later so UnCSS can search for used classes in the others assets.
gulp.task( 'build:production',
	gulp.series(setProductionMode, clean, javascript));

// Build the site and watch for file changes
gulp.task( 'default',
	gulp.series(clean, javascript, gulp.parallel(watch)));

// This happens every time a build starts
function clean(done) {
	rimraf('js', done);
}

// In production, the file is minified
function javascript() {
	return gulp.src(PATHS.entries)
		.pipe(named())
		.pipe($.sourcemaps.init())
		.pipe($.if(PRODUCTION, $.uglify()
			.on('error', e => { console.log(e); })
		))
		.pipe($.if(!PRODUCTION, $.sourcemaps.write()))
		.pipe(gulp.dest('js'));
}

// Build the "dist" folder by running all of the below tasks
// Sass must be run later so UnCSS can search for used classes in the others assets.
gulp.task(
	'build:release',
	gulp.series(
		setProductionMode,
		clean,
		bumpPluginFile,
		bumpPackageJson,
		bumpReadmeStableTag,
		bumpReadmeMDStableTag,
		bumpComposerJson
	)
);

/**
 * Bump the version number within the define method of our plugin file
 * PHP Constant: example `define( 'SIMPLE_SUBTITLES_VERSION', '1.0.0' );`
 *
 * Bump the version number within our meta data of the plugin file
 *
 * Update the release date with today's date
 *
 * @since 1.0
 *
 * @return {*}
 */
function bumpPluginFile(done) {

	let constant = 'SIMPLE_SUBTITLES_PLUGIN_VERSION';
	let define_bump_obj = {
		key: constant,
		regex: new RegExp('([<|\'|"]?(' + constant + ')[>|\'|"]?[ ]*[:=,]?[ ]*[\'|"]?[a-z]?)(\\d+.\\d+.\\d+)(-[0-9A-Za-z.-]+)?(\\+[0-9A-Za-z\\.-]+)?([\'|"|<]?)', 'ig')
	};

	let bump_obj = {
		key: 'Version',
	};

	if (VERSION_BUMP) {
		bump_obj.version        = VERSION_BUMP;
		define_bump_obj.version = VERSION_BUMP;
	}

	let today = getReleaseDate();

	return gulp.src('./simple-subtitles.php')
		.pipe($.bump(bump_obj))
		.pipe($.bump(define_bump_obj))
		.pipe($.replace(/(((0)[0-9])|((1)[0-2]))(\/)([0-2][0-9]|(3)[0-1])(\/)\d{4}/ig, today))
		.pipe(through2.obj(function (file, enc, cb) {
			let date        = new Date();
			file.stat.atime = date;
			file.stat.mtime = date;
			cb(null, file);
		}))
		.pipe(gulp.dest('./'));
}

/**
 * Update the what's new template with the date of the release instead of having to manually update it every release
 *
 * @since 3.0.3
 *
 * @return {*}
 */
function getReleaseDate() {
	let today = new Date();
	let dd    = String(today.getDate()).padStart(2, '0');
	let mm    = String(today.getMonth() + 1).padStart(2, '0');
	let yyyy  = today.getFullYear();

	return mm + '/' + dd + '/' + yyyy;
}

/**
 * Bump the composer.json
 *
 * @since 3.0.3
 *
 * @return {*}
 */
function bumpComposerJson() {

	let bump_obj = {
		key: 'version'
	};

	if (VERSION_BUMP) {
		bump_obj.version = VERSION_BUMP;
	}

	return gulp.src('./composer.json')
		.pipe($.bump(bump_obj))
		.pipe(through2.obj(function (file, enc, cb) {
			let date = new Date();
			file.stat.atime = date;
			file.stat.mtime = date;
			cb(null, file);
		}))
		.pipe(gulp.dest('.'));
}

/**
 * Bump readme file stable tag to our latest version.
 *
 * @since 2.2.0
 *
 * @return {*}
 */
function bumpReadmeStableTag() {

	let bump_obj = {key: "Stable tag"};

	if (VERSION_BUMP) {
		bump_obj.version = VERSION_BUMP;
	}

	return gulp.src('./readme.txt')
		.pipe($.bump(bump_obj))
		.pipe(through2.obj(function (file, enc, cb) {
			let date = new Date();
			file.stat.atime = date;
			file.stat.mtime = date;
			cb(null, file);
		}))
		.pipe(gulp.dest('./'));
}

/**
 * Bump readme file stable tag to our latest version.
 *
 * @since 2.2.0
 *
 * @return {*}
 */
function bumpReadmeMDStableTag() {

	let constant = '## Latest Release';

	let bump_obj = {
		key: 'Latest Release',
	};

	if (VERSION_BUMP) {
		bump_obj.version = VERSION_BUMP;
	}

	let today = getReleaseDate();

	return gulp.src('./readme.md')
		.pipe($.bump(bump_obj))
		.pipe($.replace(/(((0)[0-9])|((1)[0-2]))(\/)([0-2][0-9]|(3)[0-1])(\/)\d{4}/ig, today))
		.pipe(through2.obj(function (file, enc, cb) {
			let date        = new Date();
			file.stat.atime = date;
			file.stat.mtime = date;
			cb(null, file);
		}))
		.pipe(gulp.dest('./'));
}

/**
 * Bump the package.json.
 *
@since 2.2.0
 *
 * @return {*}
 */
function bumpPackageJson() {

	let bump_obj = {
		key: 'version'
	};

	if (VERSION_BUMP) {
		bump_obj.version = VERSION_BUMP;
	}

	return gulp.src('./package.json')
		.pipe($.bump(bump_obj))
		.pipe(through2.obj(function (file, enc, cb) {
			let date = new Date();
			file.stat.atime = date;
			file.stat.mtime = date;
			cb(null, file);
		}))
		.pipe(gulp.dest('.'));
}
