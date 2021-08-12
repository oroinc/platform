define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');
    const _ = require('underscore');

    const WidgetPickerModel = BaseModel.extend({
        defaults: {
            dialogIcon: '',
            title: '',
            widgetName: '',
            description: '',
            isNew: false,
            added: 0
        },

        /**
         * @inheritdoc
         */
        constructor: function WidgetPickerModel(attrs, options) {
            WidgetPickerModel.__super__.constructor.call(this, attrs, options);
        },

        /**
         *
         * @returns {String}
         */
        getName: function() {
            return this.get('widgetName');
        },

        /**
         * @returns {Array}
         */
        getData: function() {
            const attributes = _.clone(this.getAttributes());
            delete attributes.added;
            return attributes;
        },

        increaseAddedCounter: function() {
            this.set('added', this.get('added') + 1);
        }
    });

    return WidgetPickerModel;
});
