define(['jquery', 'underscore', 'routing', 'orotranslation/js/translator', 'oroui/js/mediator', 'oroui/js/messenger'],
function($, _, routing, __, mediator, messenger) {
    'use strict';

    var routeName = 'oro_imap_connection_check';
    var prefix = 'oro_imap_configuration';

    /**
     * Initialize component
     *
     * @param {Object} options
     * @param {string} options.elementNamePrototype
     */
    return function(options) {
        if (options.elementNamePrototype) {
            var url;

            var forEntity = options.forEntity || 'user';
            var organization = options.organization || '';

            var $el = $(options._sourceElement);
            var $form = $el.closest('form');

            var isNestedForm = options.elementNamePrototype.indexOf('[') !== -1;
            var elementNamePrototype = isNestedForm ? options.elementNamePrototype.replace(/(.+)\[\w+]$/, '$1') : '';

            var $criticalFields = $('.critical-field :input');
            $criticalFields.change(function() {
                $('.folder-tree').remove();
            });

            $el.click(function() {
                var data = $form.find('.check-connection').serializeArray();

                if (isNestedForm) {
                    // pick only values from needed nested form
                    data = _.filter(data, function(elData) {
                        return elData.name.indexOf(elementNamePrototype) === 0;
                    });
                    // transform names
                    data = _.map(data, function(field) {
                        field.name = field.name.replace(/.+\[(.+)]$/, prefix + '[$1]');

                        return field;
                    });
                }

                url = routing.generate(routeName);

                var delimiter;
                var extraQuery;

                if (options.id !== null) {
                    extraQuery = 'id=' + options.id;
                    delimiter = url.indexOf('?') === -1 ? '?' : '&';

                    url += (delimiter + extraQuery);
                }

                extraQuery = 'for_entity=' + forEntity;
                delimiter = url.indexOf('?') === -1 ? '?' : '&';
                url += (delimiter + extraQuery + '&organization=' + organization);

                mediator.execute('showLoading');
                $('.folder-tree').remove();
                $.post(url, data)
                    .done(function(response) {
                        if (response.imap) {
                            if (response.imap.error) {
                                messenger.notificationFlashMessage('error', __('oro.imap.connection.imap.error'), {
                                    container: $el.parent(),
                                    delay: 5000
                                });
                            } else {
                                messenger.notificationFlashMessage('success', __('oro.imap.connection.imap.success'), {
                                    container: $el.parent(),
                                    delay: 5000
                                });
                                $el.parent().parent().parent().append(response.imap.folders);
                            }
                        }
                        if (response.smtp) {
                            if (response.smtp.error) {
                                messenger.notificationFlashMessage('error', __('oro.imap.connection.smtp.error'), {
                                    container: $el.parent(),
                                    delay: 5000
                                });
                            } else {
                                messenger.notificationFlashMessage('success', __('oro.imap.connection.smtp.success'), {
                                    container: $el.parent(),
                                    delay: 5000
                                });
                            }
                        }
                    }, 'json')
                    .error(function() {
                        messenger.notificationFlashMessage('error', __('oro.imap.connection.error'), {
                            container: $el.parent(),
                            delay: 5000
                        });
                    })
                    .always(function() {
                        mediator.execute('hideLoading');
                    });
            });
        } else {
            // unable to initialize
            $(options._sourceElement).remove();
        }
    };
});
