define(function(require) {
    'use strict';

    var PermissionReadOnlyView;
    var BaseView = require('oroui/js/app/views/base/view');
    var accessLevels = require('orouser/js/constants/access-levels');

    PermissionReadOnlyView = BaseView.extend({
        tagName: 'li',

        className: 'action-permissions__item dropdown',

        template: require('tpl!orouser/templates/datagrid/permission/permission-readonly-view.html'),

        /**
         * @inheritDoc
         */
        constructor: function PermissionReadOnlyView() {
            PermissionReadOnlyView.__super__.constructor.apply(this, arguments);
        },

        getTemplateData: function() {
            var data = PermissionReadOnlyView.__super__.getTemplateData.apply(this, arguments);
            data.noAccess = accessLevels.NONE === this.model.get('access_level');
            return data;
        }
    });

    return PermissionReadOnlyView;
});
