define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const AbstractAction = require('oro/datagrid/action/abstract-action');
    const FiltersManager = require('orofilter/js/filters-manager');

    const ToggleFiltersAction = AbstractAction.extend({
        initialize: function(options) {
            const opts = options || {};

            if (!opts.datagrid) {
                throw new TypeError('"datagrid" is required');
            }

            this.listenTo(opts.datagrid, 'filterManager:connected', function() {
                this.onFilterManagerModeChange(this.datagrid.filterManager.getViewMode());
                this.listenTo(this.datagrid.filterManager, 'changeViewMode', this.onFilterManagerModeChange);
            });

            ToggleFiltersAction.__super__.initialize.call(this, options);
        },

        execute: function() {
            const newMode = this.datagrid.filterManager.getViewMode() === FiltersManager.STATE_VIEW_MODE
                ? FiltersManager.MANAGE_VIEW_MODE : FiltersManager.STATE_VIEW_MODE;

            this.datagrid.filterManager.setViewMode(newMode);
        },

        toggleFilters: function(mode) {
            if (mode === FiltersManager.STATE_VIEW_MODE && this.datagrid.filterManager.$el.is(':visible')) {
                this.datagrid.filterManager.hide();
            } else if (mode === FiltersManager.MANAGE_VIEW_MODE && this.datagrid.filterManager.hasFilters() &&
                !this.datagrid.filterManager.$el.is(':visible')
            ) {
                this.datagrid.filterManager.show();
            }
        },

        onFilterManagerModeChange: function(mode) {
            if (this.datagrid.filterManager.getViewMode() !== mode) {
                this.toggleFilters(mode);
            }

            if (this.launcherInstance) {
                this.launcherInstance.$el.toggleClass('pressed', mode === FiltersManager.MANAGE_VIEW_MODE);
            }

            mediator.trigger('layout:adjustHeight');
        }
    });

    return ToggleFiltersAction;
});
