define(function(require) {
    'use strict';

    var ShowColumnManagerAction;
    var AbstractAction = require('./abstract-action');
    var ColumnManagerLauncher = require('orodatagrid/js/datagrid/column-manager-launcher');

    ShowColumnManagerAction = AbstractAction.extend({
        launcher: ColumnManagerLauncher,

        /**
         * @inheritDoc
         */
        execute: function() {
            // do nothing
        }
    });

    return ShowColumnManagerAction;
});
