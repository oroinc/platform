import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
const options = {};

function onClick(e) {
    e.preventDefault();

    const method = $(e.currentTarget).data('method');
    const url = $(e.currentTarget).data('url');
    const redirect = $(e.currentTarget).data('redirect');
    const successMessage = $(e.currentTarget).data('success-message');
    const errorMessage = $(e.currentTarget).data('error-message');

    mediator.execute('showLoading');

    $.ajax({
        url: url,
        type: method,
        success: function(data) {
            mediator.execute(
                'showFlashMessage',
                'success',
                data && data.message ? data.message : __(successMessage)
            );
            mediator.execute('redirectTo', {url: redirect}, {redirect: true});
        },
        errorHandlerMessage: function(event, response) {
            const responseText = JSON.parse(response.responseText);
            return responseText.message ? responseText.message : __(errorMessage);
        },
        complete: function() {
            mediator.execute('hideLoading');
        }
    });
}

export default function(additionalOptions) {
    _.extend(options, additionalOptions || {});
    const button = options._sourceElement;
    button.on('click', onClick);
};
