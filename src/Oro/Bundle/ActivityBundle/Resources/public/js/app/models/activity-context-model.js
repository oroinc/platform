import BaseModel from 'oroui/js/app/models/base/model';

/**
 * @export  oroactivity/js/app/models/activity-context-model
 */
const ActivityContextModel = BaseModel.extend({
    defaults: {
        label: '',
        first: '',
        className: '',
        gridName: ''
    },

    /**
     * @inheritdoc
     */
    constructor: function ActivityContextModel(...args) {
        ActivityContextModel.__super__.constructor.apply(this, args);
    }
});

export default ActivityContextModel;
