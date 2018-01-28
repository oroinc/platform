define(['jquery', 'oroui/js/mediator'], function($, mediator) {
    'use strict';

    var FIELD_SELECTOR = '[name="oro_email_mailbox[unboundRules]"]';
    var DELIMITER = ',';

    return {
        init: function(deferred, options) {
            if (options.metadata.options.urlParams.mailbox) {
                deferred.resolve();
                return;
            }

            options.gridPromise.done(function(grid) {
                grid.listenTo(mediator, 'auto_response_rule:save', function(id) {
                    var param = {};
                    param[options.inputName] = {ids: [id]};
                    var paramString = $.param(param);

                    var currentUrl = grid.collection.url;
                    var operand = currentUrl.indexOf('?') === -1 ? '?' : '&';
                    grid.collection.url = currentUrl + operand + paramString;

                    var $field = $(FIELD_SELECTOR);
                    var val = $field.val();
                    var ids = val ? val.split(DELIMITER) : [];
                    ids.push(id);
                    $field.val(ids.join(DELIMITER));
                });

                deferred.resolve();
            }).fail(function() {
                deferred.reject();
            });
        }
    };
});
