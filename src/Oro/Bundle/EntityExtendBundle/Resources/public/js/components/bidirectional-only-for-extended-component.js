define(function(require) {
    'use strict';

    var BidirectionalOnlyForExtendedComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    BidirectionalOnlyForExtendedComponent = BaseComponent.extend({
        initialize: function(options) {
            var targetEntityField = $('#' + options.targetEntityId);
            var bidirectionalField = $('#' + options.bidirectionalId);
            targetEntityField.change(function() {
                if (_.indexOf(options.nonExtendedEntitiesClassNames, targetEntityField.val().trim()) !== -1) {
                    bidirectionalField.val('0').trigger('change');
                    bidirectionalField.select2('enable', false);
                } else {
                    bidirectionalField.select2('enable', true);
                }
            });
        }
    });

    return BidirectionalOnlyForExtendedComponent;
});
