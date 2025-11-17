import BaseCollectionView from 'oroui/js/app/views/base/collection-view';
import PermissionReadOnlyView from 'orouser/js/datagrid/permission/permission-readonly-view';
import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orouser/templates/datagrid/action-permissions-field-view.html';

const ActionPermissionsReadonlyFieldView = BaseView.extend({
    autoRender: false,

    animationDuration: 0,

    className: 'field-permission-container',

    template,

    permissionView: PermissionReadOnlyView,

    /**
     * @inheritdoc
     */
    constructor: function ActionPermissionsReadonlyFieldView(options) {
        ActionPermissionsReadonlyFieldView.__super__.constructor.call(this, options);
    },

    render: function() {
        ActionPermissionsReadonlyFieldView.__super__.render.call(this);
        this.subview('permissions-items', new BaseCollectionView({
            el: this.$('[data-name=field-permissions-items]'),
            tagName: 'ul',
            className: 'action-permissions',
            animationDuration: 0,
            collection: this.model.get('permissions'),
            itemView: this.permissionView
        }));
        return this;
    }
});

export default ActionPermissionsReadonlyFieldView;
