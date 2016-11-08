define(function(require) {
    'use strict';

    var MultiCheckboxView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    MultiCheckboxView = BaseView.extend({

        autoRender: true,

        template: require('tpl!oroform/templates/multi-checkbox-view.html'),

        VALUES_SEPARATOR: ',',

        boundInput: null,

        items: null,

        events: {
            'change input[type=checkbox]': 'onChange'
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, 'boundInput', 'items'));
            MultiCheckboxView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function() {
            var data = MultiCheckboxView.__super__.getTemplateData.apply(this, arguments);
            data.values = this.getValue();
            data.items = this.items;
            return data;
        },

        onChange: function(e) {
            var values = this.getValue();
            if (e.target.checked && _.indexOf(values, e.target.value) === -1) {
                values.push(e.target.value);
            } else if (!e.target.checked && _.indexOf(values, e.target.value) !== -1) {
                values = _.without(values, e.target.value);
            }
            this.setValue(values);
        },

        getValue: function() {
            var value = this.boundInput.val();
            return value.length > 0 ? value.split(this.VALUES_SEPARATOR) : [];
        },

        setValue: function(values) {
            var oldValue = this.getValue();
            if (oldValue.length !== values.length || _.difference(oldValue, values).length !== 0) {
                this.boundInput.val(values.join(this.VALUES_SEPARATOR)).trigger('change');
            }
        }
    });

    return MultiCheckboxView;
});
