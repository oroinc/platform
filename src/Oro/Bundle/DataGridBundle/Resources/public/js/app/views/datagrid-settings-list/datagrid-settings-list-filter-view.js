define(function(require) {
    'use strict';

    var DatagridSettingsListFilterView;
    var template = require('tpl!orodatagrid/templates/datagrid-settings/datagrid-settings-filter.html');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @class DatagridSettingsListFilterView
     * @extends BaseView
     */
    DatagridSettingsListFilterView = BaseView.extend({
        /**
         * @inheritDoc
         */
        template: template,

        /**
         * @inheritDoc
         */
        autoRender: true,

        /**
         * @inheritDoc
         */
        events: {
            'input [data-role="datagrid-settings-search"]': 'onSearch',
            'click [data-role="datagrid-settings-clear-search"]': 'onClearSearch',
            'click [data-role="datagrid-settings-show-all"]': 'onShowAll',
            'click [data-role="datagrid-settings-show-selected"]': 'onShowSelected'
        },

        /**
         * @inheritDoc
         */
        listen: {
            'change model': 'updateView'
        },

        /**
         * @inheritDoc
         */
        constructor: function DatagridSettingsListFilterView() {
            DatagridSettingsListFilterView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.onSearch = _.debounce(this.onSearch, 100);
            DatagridSettingsListFilterView.__super__.initialize.apply(this, arguments);
        },

        /**
         * Update view
         */
        updateView: function() {
            var search = this.model.get('search');
            var renderable = Boolean(this.model.get('renderable'));
            this.$('[data-role="datagrid-settings-search"]').val(search);
            this.$('[data-role="datagrid-settings-search-wrapper"]').toggleClass('empty', !search.length);
            this.$('[data-role="datagrid-settings-show-all"]').toggleClass('active', !renderable);
            this.$('[data-role="datagrid-settings-show-selected"]').toggleClass('active', renderable);

            mediator.trigger('layout:reposition');
        },

        /**
         * Set model search property by input value
         * @param {jQuery.Event} e
         */
        onSearch: function(e) {
            this.model.set('search', e.currentTarget.value);
        },

        /**
         * Clear search value
         * @param {jQuery.Event} e
         */
        onClearSearch: function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.$('[data-role="datagrid-settings-search"]').focus();
            this.model.set('search', '');

            mediator.trigger('layout:reposition');
        },

        /**
         * Show element
         * @param {jQuery.Event} e
         */
        onShowAll: function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.model.set('renderable', false);
        },

        /**
         * Show selected element
         * @param {jQuery.Event} e
         */
        onShowSelected: function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.model.set('renderable', true);
        }
    });

    return DatagridSettingsListFilterView;
});
