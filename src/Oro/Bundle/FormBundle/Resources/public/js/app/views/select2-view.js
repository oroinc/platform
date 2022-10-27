define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    require('jquery.select2');

    const Select2View = BaseView.extend({
        events: {
            'change': 'onChange',
            'select2-data-loaded': 'onDataLoaded'
        },

        /**
         * Use for jQuery select2 plugin initialization
         */
        select2Config: {},

        autoRender: true,

        /**
         * @inheritdoc
         */
        constructor: function Select2View(options) {
            Select2View.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            this.select2Config = _.result(options, 'select2Config') || _.extend({}, this.select2Config);

            const $emptyOption = this.$el.find('option[value=""]');
            // "Required" attribute is not allowed for '<input type="hidden">
            // such input might be used as the initializing element for Select2
            const notRequired = this.$el.is(':hidden') ? !this.$el.attr('x-required') : !this.$el[0].required;

            if (this.select2Config.allowClear === undefined && (notRequired || $emptyOption.length)) {
                this.select2Config.allowClear = true;
            }
            if (this.select2Config.allowClear && !this.select2Config.placeholderOption && $emptyOption.length) {
                this.select2Config.placeholderOption = function() {
                    return $emptyOption;
                };
            }
        },

        render: function() {
            this.undelegateEvents();
            this.$el.prop('readonly', this.$el.is('[readonly]'));
            this.$el.inputWidget('create', 'select2', {
                initializeOptions: this.select2Config
            });
            if (this.select2Config.onAfterInit) {
                this.select2Config.onAfterInit(this.$el.data('select2'));
            }
            this.delegateEvents();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.select2Config;
            this.$el.inputWidget('dispose');
            Select2View.__super__.dispose.call(this);
        },

        onChange: function(e) {
            if (this.select2Config.multiple) {
                // to update size of container, e.g. dialog
                this.$el.trigger('content:changed');
            }
        },

        onDataLoaded: function(e) {
            if (this.select2Config.multiple) {
                // to update size of container, e.g. dialog
                this.$el.trigger('content:changed');
            }
        },

        reset: function() {
            this.setValue('');
        },

        getValue: function() {
            return this.$el.inputWidget('val');
        },

        setValue: function(value) {
            this.$el.inputWidget('val', value, true);
        },

        getData: function() {
            return this.$el.inputWidget('data');
        },

        setData: function(data) {
            this.$el.inputWidget('data', data);
        },

        refresh: function() {
            return this.$el.inputWidget('refresh');
        }
    });

    return Select2View;
});
