import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';

export default function(options) {
    options._sourceElement.click(function(e) {
        e.preventDefault();
        mediator.execute('showLoading');
        $.post({
            url: $(e.target).data('url')
        }).done(function(response) {
            if (!response.successful) {
                mediator.execute(
                    'showFlashMessage',
                    'error',
                    response.message ? response.message : __('oro.ui.unexpected_error')
                );
            } else if (options.reloadRequired) {
                mediator.once('page:afterChange', function() {
                    mediator.execute('showFlashMessage', 'success', response.message);
                });
                mediator.execute('refreshPage');
            } else {
                mediator.execute('showFlashMessage', 'success', response.message);
            }
        }).always(function() {
            mediator.execute('hideLoading');
        });
    });
};
