function haveIBeenPwnedPasswordCheck(plainPassword) {
    const plainPasswordSHA1 = CryptoJS.SHA1(plainPassword).toString().toUpperCase();
    const plainPasswordSHA1Prefix = plainPasswordSHA1.slice(0, 5);
    const plainPasswordSHA1Suffix = plainPasswordSHA1.slice(5);

    return new Promise((resolve, reject) => {
        let didTimeout = false;

        // Defaults to NOT breached in case of high latency.
        const latencyTimeout = setTimeout(() => {
            console.log('timeout');
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

                if (breachedPasswordsSHA1Suffixes.indexOf(plainPasswordSHA1Suffix) !== -1) {
                    console.log('breached');
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

async function checkPasswordStrength() {
    const passwordField = $('#appbundle_user_plainPassword_first');
    const plainPassword = passwordField.val();
    const passwordLength = plainPassword.length;

    if (passwordLength === 0) {
        return;
    }

    const passwordBreached = await haveIBeenPwnedPasswordCheck(plainPassword);

    let passwordStrength = '';

    if (passwordLength < 8 || passwordBreached) {
        passwordStrength = 'weak';
    } else if (passwordLength >= 8 && passwordLength < 16) {
        passwordStrength = 'good';
    } else {
        passwordStrength = 'great';
    }

    switch (passwordStrength) {
        case 'weak':
            // TODO: display weak meter
            break;
        case 'good':
            // TODO: display good meter
            break;
        case 'excellent':
            // TODO: display excellent meter
            break;
    }
}

let typingTimer;

body.on('keyup', '#appbundle_user_plainPassword_first', () => {
    clearTimeout(typingTimer);

    typingTimer = setTimeout(() => {
        checkPasswordStrength();
    }, 250);
});