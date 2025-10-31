import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
const options = {
    successMessage: 'oro.email.menu.mark_unread.success.message',
    errorMessage: 'oro.email.menu.mark_unread.error.message',
    redirect: '/'
};

function onClick(e) {
    e.preventDefault();

    const url = $(e.target).data('url');
    mediator.execute('showLoading');
    $.post({
        url: url,
        errorHandlerMessage: __(options.errorMessage)
    }).done(function() {
        mediator.execute('showFlashMessage', 'success', __(options.successMessage));
        mediator.execute('redirectTo', {url: options.redirect}, {redirect: true});
    }).always(function() {
        mediator.execute('hideLoading');
    });
}

export default function(additionalOptions) {
    _.extend(options, additionalOptions || {});
    const button = options._sourceElement;
    button.on('click', onClick);
};
