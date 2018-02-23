define(function(require) {
    'use strict';

    var SelectCreateInlineTypeAsyncView;
    var _ = require('underscore');
    var SelectCreateInlineTypeView = require('oroform/js/app/views/select-create-inline-type-view');

    SelectCreateInlineTypeAsyncView = SelectCreateInlineTypeView.extend({
        events: {
            'select2-data-request .select2': 'onSelect2Request',
            'select2-data-loaded .select2': 'onSelect2Loaded'
        },

        /**
         * @inheritDoc
         */
        constructor: function SelectCreateInlineTypeAsyncView() {
            SelectCreateInlineTypeAsyncView.__super__.constructor.apply(this, arguments);
        },

        onGridRowSelect: function() {
            SelectCreateInlineTypeAsyncView.__super__.onGridRowSelect.apply(this, arguments);
            this.dialogWidget.hide();
        },

        onCreate: function(e) {
            SelectCreateInlineTypeAsyncView.__super__.onCreate.apply(this, arguments);
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
