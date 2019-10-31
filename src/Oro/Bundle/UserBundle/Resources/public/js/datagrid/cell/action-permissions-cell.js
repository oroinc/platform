define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const ActionPermissionsReadonlyCell = BaseView.extend({
        /**
         * @inheritDoc
         */
        constructor: function ActionPermissionsReadonlyCell(options) {
            ActionPermissionsReadonlyCell.__super__.constructor.call(this, options);
        }
    });

    return ActionPermissionsReadonlyCell;
});
