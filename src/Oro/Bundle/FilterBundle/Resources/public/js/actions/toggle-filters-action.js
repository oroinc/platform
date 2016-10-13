define(function(require) {
    'use strict';

    var ToggleFiltersAction;
    var _ = require('underscore');
    var AbstractAction = require('oro/datagrid/action/abstract-action');
    var FiltersManager = require('orofilter/js/filters-manager');

    ToggleFiltersAction =  AbstractAction.extend({
        filterManagerMode: NaN,

        initialize: function(options) {
            var opts = options || {};

            if (!opts.datagrid) {
                throw new TypeError('"datagrid" is required');
            }

            opts.datagrid.on('filterManager:connected', _.bind(function() {
                this.onFilterManagerModeChange(this.datagrid.filterManager.getMode());
                this.listenTo(this.datagrid.filterManager, 'changeMode', _.bind(this.onFilterManagerModeChange, this));
            }, this));

            ToggleFiltersAction.__super__.initialize.apply(this, arguments);
        },

        execute: function() {
            var newMode = this.datagrid.filterManager.getMode() === FiltersManager.VIEW_MODE ?
                FiltersManager.MANAGE_MODE : FiltersManager.VIEW_MODE;
            this.datagrid.filterManager.setMode(newMode);
        },

        onFilterManagerModeChange: function(mode) {
            this.launcherInstanse.$el.toggleClass('pressed', mode === FiltersManager.MANAGE_MODE);
        }
    });

    return ToggleFiltersAction;
});
