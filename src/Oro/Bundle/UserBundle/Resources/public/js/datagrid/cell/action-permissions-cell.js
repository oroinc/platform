define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const Backgrid = require('backgrid');

    const ActionPermissionsReadonlyCell = BaseView.extend({
        optionNames: ['column'],

        _attributes: Backgrid.Cell.prototype._attributes,

        /**
         * @inheritdoc
         */
        constructor: function ActionPermissionsReadonlyCell(options) {
            ActionPermissionsReadonlyCell.__super__.constructor.call(this, options);
        }
    });

    return ActionPermissionsReadonlyCell;
});
