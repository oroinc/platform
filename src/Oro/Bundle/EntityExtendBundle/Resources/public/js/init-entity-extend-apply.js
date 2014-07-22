/*jshint browser:true*/
/*jslint nomen:true, browser:true*/
/*global require*/
require(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/modal', 'oroui/js/mediator'
    ], function ($, _, __, Modal, mediator) {
    'use strict';
    $(function () {
        $(document).on('click', '.entity-extend-apply', function (e) {
            var el = $(this),
                message = el.data('message'),
                title = __('Schema update confirmation'),
                content = '<p>' + __('Your config changes will be applied to schema.') + '</p>' +
                    '</p>' + __('It may take few minutes...') + '</p>',
                /** @type oro.Modal */
                confirmUpdate = new Modal({
                    allowCancel: true,
                    cancelText: __('Cancel'),
                    okText: __('Yes, Proceed'),
                    title: title,
                    content: content
                });

            function execute() {
                var url, delimiter, modal, progress;

                url = $(el).data('url');
                delimiter = url.indexOf('?') > -1 ? '&' : '?';
                url = url + delimiter + '_enableContentProviders=mainMenu';

                progress = $('#progressbar').clone();
                progress.removeAttr('id').find('h3').remove();

                modal = new Modal({
                    allowCancel: false,
                    title: title,
                    content: content
                });
                modal.open();
                modal.$el.find('.modal-footer').html(progress);
                progress.show();

                mediator.once('page:request', function () {
                    mediator.execute('hideLoading');
                    mediator.once('page:beforeChange', function () {
                        modal.close();
                    });
                });

                mediator.execute('redirectTo', {url: url});
            }

            confirmUpdate.on('ok', execute);
            confirmUpdate.open();

            return false;
        });
    });
});
