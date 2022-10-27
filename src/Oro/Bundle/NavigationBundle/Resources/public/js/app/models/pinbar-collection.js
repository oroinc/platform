define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const BaseCollection = require('oronavigation/js/app/models/base/collection');

    const PinbarCollection = BaseCollection.extend({
        /**
         * @inheritdoc
         */
        constructor: function PinbarCollection(...args) {
            PinbarCollection.__super__.constructor.apply(this, args);
        },

        getCurrentModel() {
            return this.find(model => {
                return model.get('url') !== null &&
                    mediator.execute('compareNormalizedUrl', model.get('url'), {ignoreGetParameters: ['restore']});
            });
        }
    });

    return PinbarCollection;
});
