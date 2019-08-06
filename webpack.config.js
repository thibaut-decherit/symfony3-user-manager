const Encore = require('@symfony/webpack-encore');

Encore

    // Directory where compiled assets will be stored.
    .setOutputPath('web/build/')

    // Public path used by the web server to access the output path.
    .setPublicPath('/build')

    // Only needed for CDN's or sub-directory deploy.
    // .setManifestKeyPrefix('build/')

    // Main entry for JS required globally. File includes a reference to assets/css/app.scss for CSS required globally.
    .addEntry('app', './assets/js/app.js')

    /*
     Entries for JS tied to a specific page/feature.
     Each file can optionally include a reference to a CSS file tied to the same page/feature.
     */
    .addEntry('login', './assets/js/views/user/login.js')
    .addEntry('password-change', './assets/js/components/user/password-change.js')
    .addEntry('password-strength-meter', './assets/js/components/password-strength-meter.js')
    .addEntry('registration', './assets/js/views/user/registration.js')
    .addEntry('user-information', './assets/js/components/user/user-information.js')

    // Splits entries into chunks to avoid code duplication (e.g. two page-tied JS files both importing jQuery).
    .splitEntryChunks()

    // Allows sass/scss files to be processed.
    .enableSassLoader()

    // Allows legacy applications to use $/jQuery as a global variable.
    .autoProvidejQuery()

    .enableSourceMaps(!Encore.isProduction())

    // Enables hashed filenames (e.g. app.abc123.css). It forces browser to clear old assets from cache.
    .enableVersioning()

    // Purges the outputPath directory before each build (doesn't work on subsequent builds triggered by --watch).
    .cleanupOutputBeforeBuild()

    // Requires an extra script tag for runtime.js which must be loaded before any other script tag.
    .enableSingleRuntimeChunk()

    // Uncomment if you use TypeScript.
    // .enableTypeScriptLoader()

    // Shows OS notifications when builds finish/fail.
    // .enableBuildNotifications()

;

// Exports the final configuration.
module.exports = Encore.getWebpackConfig();
