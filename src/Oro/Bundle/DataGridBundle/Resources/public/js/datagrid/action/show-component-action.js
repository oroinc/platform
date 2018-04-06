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
        constructor: function ShowComponentAction() {
            ShowComponentAction.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        execute: function() {
            // do nothing
        }
    });

    return ShowComponentAction;
});
