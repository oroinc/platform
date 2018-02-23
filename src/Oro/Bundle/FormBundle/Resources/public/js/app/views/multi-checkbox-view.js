define(function(require) {
    'use strict';

    var MultiCheckboxView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    MultiCheckboxView = BaseView.extend({
        defaults: {
            selectAttrs: {},
            value: [],
            items: null
        },

        template: require('tpl!oroform/templates/multi-checkbox-view.html'),

        events: {
            'change input[type=checkbox]': 'onCheckboxToggle'
        },

        /**
         * @inheritDoc
         */
        constructor: function MultiCheckboxView() {
            MultiCheckboxView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            var opts = {};
            $.extend(true, opts, this.defaults, options);
            _.extend(this, _.pick(opts, 'items', 'value', 'selectAttrs'));
            MultiCheckboxView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function() {
            var data = MultiCheckboxView.__super__.getTemplateData.apply(this, arguments);
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
            var values = this.getValue();
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
            var oldValue = this.getValue();
            if (!_.haveEqualSet(oldValue, values)) {
                this.value = values;
                this.getSelectElement().val(values).trigger('change');
            }
        }
    });

    return MultiCheckboxView;
});
