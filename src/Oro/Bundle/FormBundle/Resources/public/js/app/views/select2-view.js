define(function(require) {
    'use strict';

    var Select2View;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    require('jquery.select2');

    Select2View = BaseView.extend({
        events: {
            'change': 'onValueChange',
            'select2-data-loaded': 'onValueChange'
        },

        /**
         * Use for jQuery select2 plugin initialization
         */
        select2Config: {},

        autoRender: true,

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            this.select2Config = _.result(options, 'select2Config') || _.extend({}, this.select2Config);

            var $placeholder = this.$el.find('option[value=""]');
            if (this.select2Config.allowClear === undefined && (!this.$el[0].required || $placeholder.length)) {
                this.select2Config.allowClear = true;
            }
            if (this.select2Config.allowClear && !this.select2Config.placeholderOption && $placeholder.length) {
                this.select2Config.placeholderOption = function() {
                    return $placeholder;
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
            this.$el.inputWidget('dispose');
        },

        onValueChange: function() {
            if (this.select2Config.multiple) {
                // to update size of container, e.g. dialog
                this.$el.trigger('content:changed');
            }
        }
    });

    return Select2View;
});
