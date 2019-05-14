const Encore = require('@symfony/webpack-encore');

Encore

    // Directory where compiled assets will be stored.
    .setOutputPath('web/build/')

    // Public path used by the web server to access the output path.
    .setPublicPath('/build')

    // Only needed for CDN's or sub-directory deploy.
    // .setManifestKeyPrefix('build/')

    // Compiles JS into web/build/app.js.
    .addEntry('app', './assets/js/app.js')

    // Compiles SCSS into CSS at web/build/global.css.
    .addStyleEntry('global', './assets/css/global.scss')

    // Allows sass/scss files to be processed.
    .enableSassLoader()

    // Allows legacy applications to use $/jQuery as a global variable.
    .autoProvidejQuery()

    .enableSourceMaps(!Encore.isProduction())

    // Enables hashed filenames (e.g. app.abc123.css). It forces browser to clear old assets from cache.
    .enableVersioning()

    // Purges the outputPath directory before each build (doesn't work on subsequent builds triggered by --watch).
    .cleanupOutputBeforeBuild()

    // Requires an extra script tag for runtime.js which must be loaded before any other script.
    .enableSingleRuntimeChunk()

    // Uncomment if you use TypeScript.
    // .enableTypeScriptLoader()

    // Shows OS notifications when builds finish/fail.
    // .enableBuildNotifications()

;

// Exports the final configuration.
module.exports = Encore.getWebpackConfig();
