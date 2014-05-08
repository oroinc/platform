/* jshint browser:true */
/* global require */
require(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/modal', 'oronavigation/js/navigation', 'oroui/js/mediator'],
function($, _, __, Modal, Navigation, mediator) {
    'use strict';
    $(function() {
        $(document).on('click', '.entity-extend-apply', function (e) {
            var el = $(this),
                message = el.data('message'),
                navigation = Navigation.getInstance(),
                /** @type oro.Modal */
                confirmUpdate = new Modal({
                    allowCancel: true,
                    cancelText: __('Cancel'),
                    title: __('Schema update confirmation'),
                    content: '<p>' + __('Your config changes will be applied to schema.') +
                        '</p></p>' + __('It may take few minutes...') + '</p>',
                    okText: __('Yes, Proceed')
                });

            confirmUpdate.on('ok', function() {
                confirmUpdate.preventClose();

                var url = $(el).data('url'),
                    progressbar = $('#progressbar').clone();
                progressbar
                    .attr('id', 'confirmUpdateLoading')
                    .css({'display':'block', 'margin': '0 auto'})
                    .find('h3').remove();

                confirmUpdate.$content.parent().find('a.cancel').hide();
                confirmUpdate.$content.parent().find('a.close').hide();
                confirmUpdate.$content.parent().find('a.btn-primary').replaceWith(progressbar);

                $('#confirmUpdateLoading').show();

                if (navigation) {
                    mediator.once('hash_navigation_request:start', function(){
                        navigation.hideLoading();
                    });
                    mediator.once('hash_navigation_request:complete', function(){
                        confirmUpdate.close();
                    });
                    navigation.setLocation(url);

                } else {
                    window.location.href = url;
                }
            });
            confirmUpdate.open();

            return false;
        });
    });
});
