define(function(require) {
    'use strict';

    var ShowComponentAction;
    var AbstractAction = require('./abstract-action');
    var ActionComponentDropDownLauncher = require('orodatagrid/js/datagrid/action-component-dropdown-launcher');

    ShowComponentAction = AbstractAction.extend({
        launcher: ActionComponentDropDownLauncher,

        /**
         * @inheritDoc
         */
        execute: function() {
            // do nothing
        }
    });

    return ShowComponentAction;
});
