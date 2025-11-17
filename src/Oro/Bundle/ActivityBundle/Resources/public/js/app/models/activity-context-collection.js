import ActivityContextModel from './activity-context-model';
import BaseCollection from 'oroui/js/app/models/base/collection';

const ActivityContextCollection = BaseCollection.extend({
    model: ActivityContextModel,

    /**
     * @inheritdoc
     */
    constructor: function ActivityContextCollection(...args) {
        ActivityContextCollection.__super__.constructor.apply(this, args);
    }
});

export default ActivityContextCollection;
