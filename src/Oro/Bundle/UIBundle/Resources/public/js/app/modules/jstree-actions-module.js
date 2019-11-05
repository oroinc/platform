define(function(require) {
    'use strict';

    const ActionManager = require('oroui/js/jstree-action-manager');
    const ExpandActionView = require('oroui/js/app/views/jstree/expand-action-view');
    const CollapseActionView = require('oroui/js/app/views/jstree/collapse-action-view');
    const MoveActionView = require('oroui/js/app/views/jstree/move-action-view');

    /**
    * Register actions for all jstree in application
    * @example
    * You can give ActionManager a few hook types, like a string
    * ActionManager.addAction('action name', {
    *    view: SomeActionView,
    *    isAvailable: function(options) {} - should return true/false
    });
    **/

    ActionManager.addAction('expand', {
        view: ExpandActionView
    });

    ActionManager.addAction('collapse', {
        view: CollapseActionView
    });

    ActionManager.addAction('move', {
        view: MoveActionView,
        isAvailable: function(options) {
            const move = options.actions.move || {};
            return options.$tree.data('treeView').checkboxEnabled && move.routeName;
        }
    });
});
