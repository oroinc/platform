define(function(require) {
    'use strict';

    var ActionPermissionsReadonlyCell;
    var BaseView = require('oroui/js/app/views/base/view');

    ActionPermissionsReadonlyCell = BaseView.extend({
        /**
         * @inheritDoc
         */
        constructor: function ActionPermissionsReadonlyCell() {
            ActionPermissionsReadonlyCell.__super__.constructor.apply(this, arguments);
        }
    });

    return ActionPermissionsReadonlyCell;
});
