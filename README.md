# Symfony User Manager
My take on user management with Symfony 3.4


## **Dependencies**
- Webpack Encore (SCSS and JS compiling)
- jQuery (AJAX and DOM manipulation)
- Bootstrap 4 (forms and alerts)
- Guzzle 6 (API consuming)

## **Features**

Feel free to tailor each feature to your needs.

### Internationalization:
- Content is compatible with translation files
- English translation

### Mailer:
- Service
- Account activation email
- Password reset email
- Add your own methods to send other emails

### Registration:
- Registration form with expected validations on each field (see User entity for details)
- Form submitted with AJAX to avoid refresh, so you can freely embed it in another view (e.g. in a modal)
- Custom Symfony form errors
- Bootstrap alert success with message on successful registration
- Email with activation link sent to newly registered user
  - Activation confirmation page
  - Redirect to login page with custom flash message with Bootstrap alert success if account already activated

### Login with Guard component:
- Login form, submitted with AJAX to avoid refresh, so you can freely embed it in another view (e.g. in a modal)
  - Username/email compatible field
- Bootstrap alert danger with message on wrong credentials
- Bootstrap alert danger with message on login attempt to non-activated account
- Redirect to login page on access attempt to page requiring authentication
- Remember me
- Guard
  - Add your own logic to what happens when user logs in successfully (e.g. redirect to specific route depending on user role)
  - Add your own logic to what happens when user fails to log in (e.g. count before account lockdown)

### Logout:
- Logout route

### User profile:
- Account information edit form with username and email fields (add fields as needed)
- Form embedded in parent view through Twig `{{ render(controler()) }}` , so you can group it together with password change form
- Form submitted with AJAX to avoid refresh
- Custom Symfony form errors
- Bootstrap alert success with message on successful edit

### Password change:
- Password change form submitted with AJAX to avoid refresh
- Form embedded in parent view through Twig {{ render(controler()) }} , so you can group it together with profile edit form
- Current password field
- Repeat new password field
- Custom Symfony form errors
- Bootstrap alert success with message on successful change

### Password reset:
- Password reset form with username/email compatible field
- Custom flash message with Bootstrap alert danger if no user found for submitted username/email
- Custom flash message with Bootstrap alert danger if account not yet activated
- Customizable delay between each reset request
  - Custom flash message with Bootstrap alert danger if delay has not expired, informing user of delay duration
- Customizable reset link lifetime
  - Custom flash message with Bootstrap alert danger if reset link has expired
- Email with reset link and expiration delay sent to user
- On reset success, redirect to login page and custom flash message with Bootstrap alert success

### Redirect if authenticated:
- Event listener triggered on each request through `onKernelRequest()` method 
- Redirect to homepage if authenticated user attempts to access "logged-out only" routes (e.g. login, register and password reset)
- Add your own routes and modify existing list

### Password rehash on user authentication if needed:
- Event listener triggered on login through `onSecurityInteractiveLogin` method
- Rehashes password on login if bcrypt cost has been modified in config.yml
  - Without this listener, cost change would apply only to password persisted (registration) or updated (password change or reset) after the change
  - This could be an issue if your existing users don't update their password
  - A workaround would be to force your users to change password but it is bad practice for multiple reasons and you could have to deal with distrust ("Why are you asking me that ? Have you been hacked ? Are my data safe ?")
  - This listener prevents all that by working seamlessly in the backgroup while your users log in
- Password checked through `password_needs_rehash`  method
- Bcrypt implementation
- Modify listener and config files to implement another algorithm. According to `password_needs_rehash` documentation it should work even if you switch hashing algorithm in production environment

### Haveibeenpwned API password validator:
- Prevents your users from choosing a password compromised in known data breaches
- Password validation through Troy Hunt [haveibeenpwned.com](https://haveibeenpwned.com/) API
- Custom Symfony form error
- Consider implementing this through something less strict than a validator if you think it could deter potential users (e.g. an informative message on user profile)

### Unactivated accounts removal command:
- Command to delete users registered for more than `d` days if they haven't activated their account
- Removes accounts that will most probably never be used
- Modify time between registration and removal as needed
- Execute `php bin/console app:remove-unactivated-accounts-older-than d` command (e.g. through a cron job)