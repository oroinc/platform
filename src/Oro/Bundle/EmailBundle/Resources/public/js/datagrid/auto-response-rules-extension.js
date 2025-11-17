import $ from 'jquery';
import mediator from 'oroui/js/mediator';

const FIELD_SELECTOR = '[name="oro_email_mailbox[unboundRules]"]';
const DELIMITER = ',';

export default {
    init: function(deferred, options) {
        if (options.metadata.options.urlParams.mailbox) {
            deferred.resolve();
            return;
        }

        options.gridPromise.done(function(grid) {
            grid.listenTo(mediator, 'auto_response_rule:save', function(id) {
                const $field = $(FIELD_SELECTOR);
                const val = $field.val();
                const ids = val ? val.split(DELIMITER) : [];
                ids.push(id);
                $field.val(ids.join(DELIMITER));

                grid.collection.assignUrlParams({
                    ids
                });
            });

            deferred.resolve();
        }).fail(function() {
            deferred.reject();
        });
    }
};
