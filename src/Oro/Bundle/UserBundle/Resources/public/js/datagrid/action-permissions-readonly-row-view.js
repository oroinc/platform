import _ from 'underscore';
import BaseCollection from 'oroui/js/app/models/base/collection';
import BaseCollectionView from 'oroui/js/app/views/base/collection-view';
import PermissionReadOnlyView from 'orouser/js/datagrid/permission/permission-readonly-view';
import ReadonlyFieldView from 'orouser/js/datagrid/action-permissions-readonly-field-view';
import PermissionModel from 'orouser/js/models/role/permission-model';
import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orouser/templates/datagrid/action-permissions-row-view.html';

const ActionPermissionsReadonlyRowView = BaseView.extend({
    tagName: 'tr',

    className: 'grid-row collapsed',

    autoRender: false,

    animationDuration: 0,

    template,

    permissionItemView: PermissionReadOnlyView,

    fieldItemView: ReadonlyFieldView,

    readonlyMode: true,

    optionNames: BaseView.prototype.optionNames.concat([
        'dataCollection', 'ariaRowsIndexShift'
    ]),

    /**
     * @inheritdoc
     */
    constructor: function ActionPermissionsReadonlyRowView(options) {
        ActionPermissionsReadonlyRowView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        ActionPermissionsReadonlyRowView.__super__.initialize.call(this, options);
        let fields = this.model.get('fields');
        if (typeof fields === 'string') {
            fields = JSON.parse(fields);
        }
        if (!Array.isArray(fields)) {
            fields = _.values(fields);
        }
        if (fields.length) {
            _.each(fields, function(field) {
                field.permissions = new BaseCollection(_.values(field.permissions), {
                    model: PermissionModel
                });
            });
        }
        this.model.set('fields', fields, {silent: true});
    },

    render: function() {
        ActionPermissionsReadonlyRowView.__super__.render.call(this);
        const fields = this.model.get('fields');
        this.subview('permissions-items', new BaseCollectionView({
            el: this.$('[data-name=action-permissions-items]'),
            tagName: 'ul',
            className: 'action-permissions',
            animationDuration: 0,
            collection: this.model.get('permissions'),
            itemView: this.permissionItemView
        }));
        if (fields.length > 0) {
            this.subview('fields-list', new BaseCollectionView({
                el: this.$('[data-name=fields-list]'),
                collection: new BaseCollection(fields),
                animationDuration: 0,
                itemView: this.fieldItemView
            }));
        }
        return this;
    },

    getTemplateData: function() {
        const data = ActionPermissionsReadonlyRowView.__super__.getTemplateData.call(this);

        data.ariaRowIndex = this.getAriaRowIndex();
        data.readonlyMode = this.readonlyMode;
        data.columnsCount = this.collection.length;

        return data;
    },

    _attributes: function() {
        return {
            'role': 'presentation',
            'aria-rowindex': null
        };
    },

    /**
     * @return {null|number}
     */
    getAriaRowIndex() {
        let ariaRowIndex = null;
        const indexInCollection = this.dataCollection
            .filter(model => model.get('isAuxiliary') !== true)
            .findIndex(model => model.cid === this.model.cid);

        if (indexInCollection !== -1) {
            ariaRowIndex = indexInCollection + this.ariaRowsIndexShift;
        }

        return ariaRowIndex;
    }
});

export default ActionPermissionsReadonlyRowView;
