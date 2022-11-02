define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');

    const MultiCheckboxView = BaseView.extend({
        defaults: {
            selectAttrs: {},
            value: [],
            items: null
        },

        template: require('tpl-loader!oroform/templates/multi-checkbox-view.html'),

        events: {
            'change input[type=checkbox]': 'onCheckboxToggle'
        },

        /**
         * @inheritdoc
         */
        constructor: function MultiCheckboxView(options) {
            MultiCheckboxView.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            const opts = {};
            $.extend(true, opts, this.defaults, options);
            _.extend(this, _.pick(opts, 'items', 'value', 'selectAttrs'));
            MultiCheckboxView.__super__.initialize.call(this, options);
        },

        getTemplateData: function() {
            const data = MultiCheckboxView.__super__.getTemplateData.call(this);
            data.name = this.selectAttrs.name || _.uniqueId('multi-checkbox');
            data.values = this.value;
            data.options = this.items;
            return data;
        },

        render: function() {
            MultiCheckboxView.__super__.render.call(this);
            this.getSelectElement().attr(this.selectAttrs);
            return this;
        },

        onCheckboxToggle: function(e) {
            let values = this.getValue();
            if (e.target.checked && _.indexOf(values, e.target.value) === -1) {
                values.push(e.target.value);
            } else if (!e.target.checked && _.indexOf(values, e.target.value) !== -1) {
                values = _.without(values, e.target.value);
            }
            this.setValue(values);
        },

        getSelectElement: function() {
            return this.$('[data-name="multi-checkbox-value-keeper"]');
        },

        getValue: function() {
            return this.getSelectElement().val();
        },

        setValue: function(values) {
            const oldValue = this.getValue();
            if (!_.haveEqualSet(oldValue, values)) {
                this.value = values;
                this.getSelectElement().val(values).trigger('change');
            }
        }
    });

    return MultiCheckboxView;
});
