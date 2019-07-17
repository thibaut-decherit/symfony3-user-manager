require('../../css/components/password-strength-meter.scss');
import {SHA1} from 'crypto-js';
import estimatePasswordStrength from 'zxcvbn';
import 'babel-polyfill'; // Required to use async await.

let typingTimer;

body.on('keyup', '#appbundle_user_plainPassword_first', function () {
    clearTimeout(typingTimer);

    typingTimer = setTimeout(() => {
        checkPasswordStrength($(this).val());
    }, 200);
});

async function checkPasswordStrength(plainPassword) {
    const passwordLength = plainPassword.length;

    if (passwordLength === 0) {
        updatePasswordMeter('empty');

        return;
    }

    const customBlacklist = getCustomBlacklist();
    const passwordBreached = await haveIBeenPwnedPasswordCheck(plainPassword);
    const passwordStrengthEstimation = estimatePasswordStrength(plainPassword, customBlacklist).score;

    let passwordStrength = '';

    if (passwordLength < 8 || passwordBreached || passwordStrengthEstimation < 3) {
        passwordStrength = 'weak';
    } else if (passwordLength >= 8 && passwordLength < 16 && passwordStrengthEstimation >= 3) {
        passwordStrength = 'average';
    } else {
        passwordStrength = 'good';
    }

    updatePasswordMeter(passwordStrength);
}

function getCustomBlacklist() {

    // Attempts to retrieve blacklist generated by the server on pages missing relevant data (e.g. password reset page).
    const customBlacklistJsonFromBackEnd = $('.password-strength-meter').attr('data-blacklist');
    let customBlacklistArrayFromBackEnd = [];

    // IF page contains server-side generated blacklist.
    if (typeof customBlacklistJsonFromBackEnd !== 'undefined') {
        customBlacklistArrayFromBackEnd = JSON.parse(customBlacklistJsonFromBackEnd);
    }

    // Retrieves value of current form inputs that should not be reused as a password.
    const customBlacklistFromInputs = [
        $('#appbundle_user_username').val(),
        $('#appbundle_user_email').val(),
    ];

    return customBlacklistFromInputs.concat(customBlacklistArrayFromBackEnd);
}

function haveIBeenPwnedPasswordCheck(plainPassword) {
    const plainPasswordSHA1 = SHA1(plainPassword).toString().toUpperCase();
    const plainPasswordSHA1Prefix = plainPasswordSHA1.slice(0, 5);
    const plainPasswordSHA1Suffix = plainPasswordSHA1.slice(5);

    return new Promise((resolve, reject) => {
        let didTimeout = false;

        // Defaults to NOT breached in case of high latency.
        const latencyTimeout = setTimeout(() => {
            didTimeout = true;
            resolve(false);
        }, 250);

        fetch('https://api.pwnedpasswords.com/range/' + plainPasswordSHA1Prefix)
            .then(async response => {

                // Prevents a second resolve if latencyTimeout has been triggered.
                if (didTimeout) {
                    return;
                }

                clearTimeout(latencyTimeout);

                const breachedPasswordsSHA1Suffixes = await response.text();

                if (breachedPasswordsSHA1Suffixes.includes(plainPasswordSHA1Suffix)) {
                    resolve(true);
                }

                resolve(false);
            })
            .catch(() => {

                // Prevents a second resolve if latencyTimeout has been triggered.
                if (didTimeout) {
                    return;
                }

                clearTimeout(latencyTimeout);

                resolve(false);
            })
    });
}

function updatePasswordMeter(passwordStrength) {
    const passwordMeter = $('.password-strength-meter');

    switch (passwordStrength) {
        case 'weak':
            passwordMeter.val('1');
            break;
        case 'average':
            passwordMeter.val('2');
            break;
        case 'good':
            passwordMeter.val('3');
            break;
        case 'empty':
            passwordMeter.val('0');
            break;
    }
}
