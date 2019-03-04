const $ = require('jquery');
global.$ = global.jQuery = require('jquery');
global.body = $('body');
require('bootstrap');
require('babel-polyfill'); // Required to use async await.
global.CryptoJS = require('crypto-js');

require('./registration');
require('./login');
require('./user-information');
require('./password-change');
require('./components/password-strength-meter');
