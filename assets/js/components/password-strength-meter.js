function haveIBeenPwnedPasswordCheck(plainPassword) {
    const plainPasswordSHA1 = CryptoJS.SHA1(plainPassword).toString().toUpperCase();
    const plainPasswordSHA1Prefix = plainPasswordSHA1.slice(0, 5);
    const plainPasswordSHA1Suffix = plainPasswordSHA1.slice(5);

    return new Promise((resolve, reject) => {
        fetch('https://api.pwnedpasswords.com/range/' + plainPasswordSHA1Prefix)
            .then(async response => {
                const breachedPasswordsSuffixes = await response.text();

                if (breachedPasswordsSuffixes.indexOf(plainPasswordSHA1Suffix) !== -1) {
                    console.log('breached');
                    resolve(true);
                }

                resolve(false);
            })
            .catch(() => {
                resolve(false);
            })
    });
}

async function checkPasswordStrength(plainPassword, passwordLength) {
    const passwordBreached = await haveIBeenPwnedPasswordCheck(plainPassword);

    return new Promise((resolve, reject) => {
        if (passwordLength < 8 || passwordBreached) {
            resolve('weak');
        } else if (passwordLength >= 8 && passwordLength < 16) {
            resolve('good');
        } else {
            resolve('great');
        }
    });
}

body.on('keyup', '#appbundle_user_plainPassword_first', async () => {
    const passwordField = $('#appbundle_user_plainPassword_first');
    const plainPassword = passwordField.val();
    const passwordLength = plainPassword.length;

    if (passwordLength === 0) {
        return;
    }

    const passwordStrength = await checkPasswordStrength(plainPassword, passwordLength);

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
});