const $ = require('jquery');
global.$ = global.jQuery = require('jquery');
global.body = $('body');
require('bootstrap');

require('./registration');
require('./login');
require('./user-information');
require('./password-change');
