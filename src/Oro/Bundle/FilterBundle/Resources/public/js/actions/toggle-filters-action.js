define(function(require) {
    'use strict';

    var ToggleFiltersAction;
    var mediator = require('oroui/js/mediator');
    var AbstractAction = require('oro/datagrid/action/abstract-action');
    var FiltersManager = require('orofilter/js/filters-manager');

    ToggleFiltersAction = AbstractAction.extend({
        initialize: function(options) {
            var opts = options || {};

            if (!opts.datagrid) {
                throw new TypeError('"datagrid" is required');
            }

            this.listenTo(opts.datagrid, 'filterManager:connected', function() {
                this.onFilterManagerModeChange(this.datagrid.filterManager.getViewMode());
                this.listenTo(this.datagrid.filterManager, 'changeViewMode', this.onFilterManagerModeChange);
            });

            ToggleFiltersAction.__super__.initialize.apply(this, arguments);
        },

        execute: function() {
            var newMode = this.datagrid.filterManager.getViewMode() === FiltersManager.STATE_VIEW_MODE
                ? FiltersManager.MANAGE_VIEW_MODE : FiltersManager.STATE_VIEW_MODE;

            this.datagrid.filterManager.setViewMode(newMode);
        },

        onFilterManagerModeChange: function(mode) {
            if (this.launcherInstanse) {
                this.launcherInstanse.$el.toggleClass('pressed', mode === FiltersManager.MANAGE_VIEW_MODE);
            }
            mediator.trigger('layout:adjustHeight');
        }
    });

    return ToggleFiltersAction;
});
