define(function(require) {
    'use strict';

    var ShowColumnManagerAction;
    var AbstractAction = require('./abstract-action');
    var ActionComponentDropDownLauncher = require('orodatagrid/js/datagrid/action-component-dropdown-launcher');

    ShowColumnManagerAction = AbstractAction.extend({
        launcher: ActionComponentDropDownLauncher,

        /**
         * @inheritDoc
         */
        execute: function() {
            // do nothing
        }
    });

    return ShowColumnManagerAction;
});
