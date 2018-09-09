// Registration form ajax
body.on('submit', '#ajax-form-change-password', function (e) {

    const CHANGE_PASSWORD_FORM = $('#ajax-form-change-password');

    // Prevents submit button default behaviour
    e.preventDefault();

    $.ajax({
        type: $(this).attr('method'),
        url: $(this).attr('action'),
        data: $(this).serialize()
    })
    // Triggered if response status == 200 (form is valid and data has been processed successfully)
        .done(function (response) {
            // Parses the JSON response to "unescape" the html code within
            const TEMPLATE = JSON.parse(response.template);

            CHANGE_PASSWORD_FORM.html(TEMPLATE);
            CHANGE_PASSWORD_FORM.find('.alert').removeClass('d-none');
            CHANGE_PASSWORD_FORM.find('#success-message').html(response.successMessage);
        })
        // Triggered if response status == 400 (form has errors)
        .fail(function (response) {
            // Parses the JSON response to "unescape" the html code within
            const TEMPLATE = JSON.parse(response.responseJSON.template);
            //  Replaces html content of html element id 'ajax-form-fos-user-registration' with updated form
            // (with errors and input values)
            CHANGE_PASSWORD_FORM.html(TEMPLATE);
        });
});
