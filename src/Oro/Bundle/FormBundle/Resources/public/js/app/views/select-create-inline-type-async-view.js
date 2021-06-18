define(function(require) {
    'use strict';

    const _ = require('underscore');
    const SelectCreateInlineTypeView = require('oroform/js/app/views/select-create-inline-type-view');

    const SelectCreateInlineTypeAsyncView = SelectCreateInlineTypeView.extend({
        events: {
            'select2-data-request .select2': 'onSelect2Request',
            'select2-data-loaded .select2': 'onSelect2Loaded'
        },

        /**
         * @inheritdoc
         */
        constructor: function SelectCreateInlineTypeAsyncView(options) {
            SelectCreateInlineTypeAsyncView.__super__.constructor.call(this, options);
        },

        onGridRowSelect: function(data) {
            SelectCreateInlineTypeAsyncView.__super__.onGridRowSelect.call(this, data);
            this.dialogWidget.hide();
        },

        onCreate: function(e) {
            SelectCreateInlineTypeAsyncView.__super__.onCreate.call(this, e);
            this.dialogWidget.once('beforeContentLoad', _.bind(function() {
                this.dialogWidget.hide();
                this.$el.addClass('loading');
            }, this));
        },

        onSelect2Request: function() {
            if (this.dialogWidget) {
                this.$el.addClass('loading');
            }
        },

        onSelect2Loaded: function() {
            this.$el.removeClass('loading');
        }
    });

    return SelectCreateInlineTypeAsyncView;
});
