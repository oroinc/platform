define(function(require) {
    'use strict';

    const AbstractAction = require('./abstract-action');
    const ActionComponentDropDownLauncher = require('orodatagrid/js/datagrid/action-component-dropdown-launcher');

    const ShowComponentAction = AbstractAction.extend({
        launcher: ActionComponentDropDownLauncher,

        /**
         * @inheritDoc
         */
        constructor: function ShowComponentAction(options) {
            ShowComponentAction.__super__.constructor.call(this, options);
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
