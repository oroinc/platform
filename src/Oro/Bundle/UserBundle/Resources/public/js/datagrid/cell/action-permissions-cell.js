import BaseView from 'oroui/js/app/views/base/view';
import Backgrid from 'backgrid';

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

export default ActionPermissionsReadonlyCell;
