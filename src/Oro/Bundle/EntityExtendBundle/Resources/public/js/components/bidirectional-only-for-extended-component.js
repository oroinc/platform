define(function(require) {
    'use strict';

    var BidirectionalOnlyForExtendedComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    BidirectionalOnlyForExtendedComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function BidirectionalOnlyForExtendedComponent() {
            BidirectionalOnlyForExtendedComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var targetEntityField = $('[data-name="field__target-entity"]');
            var bidirectionalField = $('[data-name="field__bidirectional"]');
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
