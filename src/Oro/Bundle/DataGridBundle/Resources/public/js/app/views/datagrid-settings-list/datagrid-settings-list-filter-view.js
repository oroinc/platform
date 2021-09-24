define(function(require) {
    'use strict';

    const template = require('tpl-loader!orodatagrid/templates/datagrid-settings/datagrid-settings-filter.html');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');

    /**
     * @class DatagridSettingsListFilterView
     * @extends BaseView
     */
    const DatagridSettingsListFilterView = BaseView.extend({
        /**
         * @inheritdoc
         */
        template: template,

        /**
         * @inheritdoc
         */
        autoRender: true,

        /**
         * @inheritdoc
         */
        events: {
            'input [data-role="datagrid-settings-search"]': 'onSearch',
            'click [data-role="datagrid-settings-clear-search"]': 'onClearSearch',
            'click [data-role="datagrid-settings-show-all"]': 'onShowAll',
            'click [data-role="datagrid-settings-show-selected"]': 'onShowSelected'
        },

        /**
         * @inheritdoc
         */
        listen: {
            'change model': 'updateView'
        },

        /**
         * @inheritdoc
         */
        constructor: function DatagridSettingsListFilterView(options) {
            DatagridSettingsListFilterView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.onSearch = _.debounce(this.onSearch, 100);
            DatagridSettingsListFilterView.__super__.initialize.call(this, options);
        },

        /**
         * Update view
         */
        updateView: function() {
            const search = this.model.get('search');
            const renderable = Boolean(this.model.get('renderable'));
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
