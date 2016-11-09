define(function(require) {
    'use strict';

    var MultiCheckboxView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    MultiCheckboxView = BaseView.extend({

        autoRender: true,

        template: require('tpl!oroform/templates/multi-checkbox-view.html'),

        VALUES_SEPARATOR: ',',

        inputName: null,

        value: [],

        items: null,

        events: {
            'change input[type=checkbox]': 'onCheckboxToggle'
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, 'inputName', 'items', 'value'));
            MultiCheckboxView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function() {
            var data = MultiCheckboxView.__super__.getTemplateData.apply(this, arguments);
            data.values = this.value;
            data.options = this.items;
            data.inputName = this.inputName;
            return data;
        },

        onCheckboxToggle: function(e) {
            var values = this.getValue();
            if (e.target.checked && _.indexOf(values, e.target.value) === -1) {
                values.push(e.target.value);
            } else if (!e.target.checked && _.indexOf(values, e.target.value) !== -1) {
                values = _.without(values, e.target.value);
            }
            this.setValue(values);
        },

        getValue: function() {
            return this.$('[name="' + this.inputName + '"]').val();
        },

        setValue: function(values) {
            var oldValue = this.getValue();
            if (oldValue.length !== values.length || _.difference(oldValue, values).length !== 0) {
                this.value = values;
                this.$('[name="' + this.inputName + '"]').val(values).trigger('change');
            }
        }
    });

    return MultiCheckboxView;
});
