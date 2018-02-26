define(function(require) {
    'use strict';

    var WidgetPickerModel;
    var BaseModel = require('oroui/js/app/models/base/model');
    var _ = require('underscore');

    WidgetPickerModel = BaseModel.extend({
        defaults: {
            dialogIcon: '',
            title: '',
            widgetName: '',
            description: '',
            isNew: false,
            added: 0
        },

        /**
         * @inheritDoc
         */
        constructor: function WidgetPickerModel() {
            WidgetPickerModel.__super__.constructor.apply(this, arguments);
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
            var attributes = _.clone(this.getAttributes());
            delete attributes.added;
            return attributes;
        },

        increaseAddedCounter: function() {
            this.set('added', this.get('added') + 1);
        }
    });

    return WidgetPickerModel;
});
