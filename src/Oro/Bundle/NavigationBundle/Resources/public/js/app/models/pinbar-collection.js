define(function(require) {
    'use strict';

    var PinbarCollection;
    var mediator = require('oroui/js/mediator');
    var BaseCollection = require('oronavigation/js/app/models/base/collection');

    PinbarCollection = BaseCollection.extend({
        /**
         * @inheritDoc
         */
        constructor: function PinbarCollection() {
            PinbarCollection.__super__.constructor.apply(this, arguments);
        },

        getCurrentModel: function() {
            return this.find(function(model) {
                return mediator.execute('compareNormalizedUrl', model.get('url'), {ignoreGetParameters: ['restore']});
            });
        }
    });

    return PinbarCollection;
});
