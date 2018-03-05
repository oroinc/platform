define(function(require) {
    'use strict';

    var Select2View;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    require('jquery.select2');

    Select2View = BaseView.extend({
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
         * @inheritDoc
         */
        constructor: function Select2View() {
            Select2View.__super__.constructor.apply(this, arguments);
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            this.select2Config = _.result(options, 'select2Config') || _.extend({}, this.select2Config);

            var $emptyOption = this.$el.find('option[value=""]');
            if (this.select2Config.allowClear === undefined && (!this.$el[0].required || $emptyOption.length)) {
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
