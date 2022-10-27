define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const BidirectionalOnlyForExtendedComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function BidirectionalOnlyForExtendedComponent(options) {
            BidirectionalOnlyForExtendedComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            const targetEntityField = $('[data-name="field__target-entity"]');
            const bidirectionalField = $('[data-name="field__bidirectional"]');
            targetEntityField.change(function() {
                if (_.indexOf(options.nonExtendedEntitiesClassNames, targetEntityField.val().trim()) !== -1) {
                    bidirectionalField.val('0').trigger('change');
                    bidirectionalField.select2('readonly', true);
                } else {
                    bidirectionalField.select2('readonly', false);
                }
            });
        }
    });

    return BidirectionalOnlyForExtendedComponent;
});
