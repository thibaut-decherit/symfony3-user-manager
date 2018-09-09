// webpack.config.js

var Encore = require('@symfony/webpack-encore');


Encore

// the project directory where all compiled assets will be stored

    .setOutputPath('web/build/')


    // the public path used by the web server to access the previous directory

    .setPublicPath('/build')


    // will create web/build/app.js and web/build/app.css

    .addEntry('app', [
        './assets/js/app.js',
        './assets/js/registration.js',
        './assets/js/login.js',
        './assets/js/user-information.js',
        './assets/js/password-change.js',
    ])


    // Seulement si le fichier css ne s'appel pas app

    .addStyleEntry('global', './assets/css/global.scss')


    // allow sass/scss files to be processed

    .enableSassLoader()


    // allow legacy applications to use $/jQuery as a global variable

    .autoProvidejQuery()


    .enableSourceMaps(!Encore.isProduction())


    // empty the outputPath dir before each build

    .cleanupOutputBeforeBuild()


    // show OS notifications when builds finish/fail

    .enableBuildNotifications()


// create hashed filenames (e.g. app.abc123.css)

// .enableVersioning()

;


// export the final configuration

module.exports = Encore.getWebpackConfig();

