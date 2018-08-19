// Registration form ajax
body.on('submit', '#ajax-form-registration', function (e) {

    const REGISTRATION_FORM = $('#ajax-form-registration');

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
            const template = JSON.parse(response.template);

            REGISTRATION_FORM.html(template);
            REGISTRATION_FORM.find('.alert').removeClass('d-none');
            REGISTRATION_FORM.find('#success-message').html(response.success_message);
        })
        // Triggered if response status == 400 (form has errors)
        .fail(function (response) {
            // Parses the JSON response to "unescape" the html code within
            const template = JSON.parse(response.responseJSON.template);
            //  Replaces html content of html element id 'ajax-form-fos-user-registration' with updated form
            // (with errors and input values)
            $('#ajax-form-registration').html(template);
        });
});
