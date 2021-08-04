define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const accessLevels = require('orouser/js/constants/access-levels');

    const PermissionReadOnlyView = BaseView.extend({
        tagName: 'li',

        className: 'action-permissions__item dropdown',

        template: require('tpl-loader!orouser/templates/datagrid/permission/permission-readonly-view.html'),

        /**
         * @inheritdoc
         */
        constructor: function PermissionReadOnlyView(options) {
            PermissionReadOnlyView.__super__.constructor.call(this, options);
        },

        getTemplateData: function() {
            const data = PermissionReadOnlyView.__super__.getTemplateData.call(this);
            data.noAccess = accessLevels.NONE === this.model.get('access_level');
            return data;
        }
    });

    return PermissionReadOnlyView;
});
