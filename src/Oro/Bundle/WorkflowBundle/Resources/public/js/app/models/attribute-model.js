define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');

    const AttributeModel = BaseModel.extend({
        defaults: {
            name: null,
            label: null,
            translated_label: null,
            type: null,
            property_path: null,
            options: null,
            translateLinks: []
        },

        /**
         * @inheritdoc
         */
        constructor: function AttributeModel(...args) {
            AttributeModel.__super__.constructor.apply(this, args);
        },

        /**
         * @inheritdoc
         */
        initialize: function() {
            if (this.get('options') === null) {
                this.set('options', {});
            }
        }
    });

    return AttributeModel;
});
