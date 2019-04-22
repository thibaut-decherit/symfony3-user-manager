import $ from 'jquery';
global.body = $('body');
import 'bootstrap';
import 'babel-polyfill'; // Required to use async await.

require('./registration');
require('./login');
require('./user-information');
require('./password-change');
require('./components/password-strength-meter');
