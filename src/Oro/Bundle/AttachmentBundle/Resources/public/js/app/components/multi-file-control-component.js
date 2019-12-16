define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const $ = require('jquery');
    const _ = require('underscore');

    const MultiFileControlComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {},

        /**
         * @inheritDoc
         */
        constructor: function MultiFileControlComponent(options) {
            MultiFileControlComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            const $el = this.options._sourceElement;
            const lineItemSortOrderSelector = 'tr td.sort-order input';

            const $lineItemSortOrder = $el.find(lineItemSortOrderSelector);
            if ($lineItemSortOrder.length === 1 && !$lineItemSortOrder.val()) {
                $lineItemSortOrder.val(1);
            }

            // process adding new item to collection
            $el.on('content:changed', function() {
                let max = 0;
                $(this).find(lineItemSortOrderSelector).each(function() {
                    max = Math.max(max, $(this).val() || 0);
                });

                $(this).find(lineItemSortOrderSelector).last().val(max + 1);
            });
        }
    });

    return MultiFileControlComponent;
});
