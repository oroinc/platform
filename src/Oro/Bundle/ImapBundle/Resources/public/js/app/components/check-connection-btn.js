define(['jquery', 'underscore', 'routing', 'orotranslation/js/translator', 'oroui/js/mediator', 'oroui/js/messenger'],
function ($, _, routing, __, mediator, messenger) {
    'use strict';

    var routeName, prefix;

    routeName = 'oro_imap_connection_check';
    prefix = 'oro_imap_configuration';

    /**
     * Initialize component
     *
     * @param {Object} options
     * @param {string} options.elementNamePrototype
     */
    return function (options) {
        if (options.elementNamePrototype) {
            var $form, $el, elementNamePrototype, isNestedForm, url;

            $el = $(options._sourceElement);
            $form = $el.closest('form');

            isNestedForm = options.elementNamePrototype.indexOf('[') !== -1;
            elementNamePrototype = isNestedForm ? options.elementNamePrototype.replace(/(.+)\[\w+]$/, '$1') : '';

            var $criticalFields = $('.critical-field :input');
            $criticalFields.change(function() {
                $('.folder-tree').remove();
            });

            $el.click(function () {
                var data = $form.serializeArray();

                if (isNestedForm) {
                    // pick only values from needed nested form
                    data = _.filter(data, function (elData) {
                        return elData.name.indexOf(elementNamePrototype) === 0;
                    });
                    // transform names
                    data = _.map(data, function (field) {
                        field.name = field.name.replace(/.+\[(.+)]$/, prefix + '[$1]');

                        return field;
                    });
                    // clear folders data
                    data = data.splice(0, 6);
                }

                url = routing.generate(routeName);
                if (options.id !== null) {
                    var extraQuery = 'id=' + options.id,
                        delimiter = url.indexOf('?') === -1 ? '?' : '&';

                    url += (delimiter + extraQuery);
                }

                mediator.execute('showLoading');
                //$el.parent().parent().parent().find('div.control-group').slice(7).remove();
                $('.folder-tree').remove();
                $.post(url, data)
                    .done(function (response) {
                        $el.parent().parent().parent().append(response);
                    })
                    .error(function () {
                        messenger.notificationFlashMessage('error', __('oro.imap.connection.error'), {
                            container: $el.parent()
                        });
                    })
                    .always(function () {
                        mediator.execute('hideLoading');
                    });
            });
        } else {
            // unable to initialize
            $(options._sourceElement).remove();
        }
    }
});
