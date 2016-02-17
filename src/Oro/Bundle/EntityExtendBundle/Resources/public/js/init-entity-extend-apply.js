require([
    'jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/modal', 'oroui/js/mediator', 'routing'
], function($, _, __, Modal, mediator, routing) {
    'use strict';

    $(function() {
        $(document).on('click', '.entity-extend-apply', function(e) {
            var el = $(this);
            var title = __('Schema update confirmation');
            var content = '<p>' + __('Your config changes will be applied to schema.') + '</p>' +
                    '<p>' + __('It may take few minutes...') + '</p>';
            /** @type oro.Modal */
            var confirmUpdate = new Modal({
                allowCancel: true,
                className: 'modal modal-primary',
                cancelText: __('Cancel'),
                okText: __('Yes, Proceed'),
                title: title,
                content: content
            });

            function execute() {
                var url = $(el).data('url');
                var progress = $('#progressbar').clone();
                progress.removeAttr('id').find('h3').remove();

                var modal = new Modal({
                    allowCancel: false,
                    className: 'modal modal-primary',
                    title: title,
                    content: content
                });
                modal.open();
                modal.$el.find('.modal-footer').html(progress);
                progress.show();

                $.post(url, function() {
                    modal.close();
                    mediator.execute(
                        'showFlashMessage',
                        'success',
                        __('oro.entity_extend.schema_updated'),
                        {afterReload: true}
                    );
                    mediator.execute('showMessage', 'info', __('Please wait until page will be reloaded...'));
                    mediator.execute('showLoading');
                    // force reload of the application to make sure 'js/routes' is reloaded
                    window.location.href = routing.generate('oro_entityconfig_index');
                }).fail(function() {
                    modal.close();
                    mediator.execute('showFlashMessage', 'error', __('oro.entity_extend.schema_update_failed'));
                });
            }

            confirmUpdate.on('ok', execute);
            confirmUpdate.open();

            return false;
        });
    });
});
