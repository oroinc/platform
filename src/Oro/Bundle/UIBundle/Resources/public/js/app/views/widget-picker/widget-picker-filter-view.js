define(function(require) {
    'use strict';

    var WidgetPickerFilterView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    WidgetPickerFilterView = BaseView.extend({
        template: require('tpl!oroui/templates/widget-picker/widget-picker-filter-view.html'),

        autoRender: true,

        events: {
            'keyup [data-role="filter-search"]': 'onSearchChange',
            'change [data-role="filter-search"]': 'onSearchChange',
            'paste [data-role="filter-search"]': 'onSearchChange',
            'mouseup [data-role="filter-search"]': 'onSearchChange',

            'click [data-role="filter-clear"]': 'onClearClick'
        },

        listen: {
            'change:search model': 'onSearch'
        },

        /**
         * @inheritDoc
         */
        constructor: function WidgetPickerFilterView(options) {
            WidgetPickerFilterView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.onSearch = _.debounce(this.onSearch, 100);
            WidgetPickerFilterView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            this.updateClassModifier();
            return WidgetPickerFilterView.__super__.render.call(this);
        },

        /**
         * Handles search input value change and update the model
         *
         * @param {Event} e
         */
        onSearchChange: function(e) {
            this.model.set('search', e.currentTarget.value);
        },

        /**
         * Handles click on clear button and sets empty search term to input
         *
         * @param {Event} e
         */
        onClearClick: function(e) {
            e.preventDefault();
            this.$('[data-role="filter-search"]').val('').change();
        },

        /**
         * Handles search term change in model
         */
        onSearch: function() {
            this.updateClassModifier();
        },

        /**
         * Updates css class modifier for the view
         */
        updateClassModifier: function() {
            var term = String(this.model.get('search'));
            this.$el.toggleClass('empty', term.length === 0);
        }
    });

    return WidgetPickerFilterView;
});
