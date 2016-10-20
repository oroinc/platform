require(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/tools',
    'oroui/js/mediator', 'bootstrap', 'jquery-ui'
], function($, _, __, tools, mediator) {
    'use strict';
    $(function () {
        $(document).on('click', '.menu-visibility-toggle-button', function (e) {
            var el = $(this);
            if (!(el.is('[disabled]') || el.hasClass('disabled'))) {
                mediator.execute('showLoading');

                $.ajax({
                    url: el.data('url'),
                    type: el.data('method'),
                    success: function () {
                        mediator.execute('addMessage', 'success', el.data('successmessage'));
                        mediator.execute('refreshPage');
                    },
                    error: function () {
                        var message;
                        message = el.data('error-message') ||
                            __('Unexpected error occurred. Please contact system administrator.');
                        mediator.execute('hideLoading');
                        mediator.execute('showMessage', 'error', message);
                    }
                });
            }

            return false;
        });
        $(document).on('click', '.menu-divider-create-button', function (e) {
            var el = $(this);
            if (!(el.is('[disabled]') || el.hasClass('disabled'))) {
                mediator.execute('showLoading');

                $.ajax({
                    url: el.data('url'),
                    type: el.data('method'),
                    success: function () {
                        mediator.execute('addMessage', 'success', el.data('successmessage'));
                        mediator.execute('refreshPage');
                    },
                    error: function () {
                        var message;
                        message = el.data('error-message') ||
                            __('Unexpected error occurred. Please contact system administrator.');
                        mediator.execute('hideLoading');
                        mediator.execute('showMessage', 'error', message);
                    }
                });
            }

            return false;
        });
    });
});
