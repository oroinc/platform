import routing from 'routing';
import BaseModel from 'oroui/js/app/models/base/model';

/**
 * @export  oroactivity/js/app/models/activity-context-activity-model
 */
const ActivityContextActivityModel = BaseModel.extend({
    route: 'oro_api_delete_activity_relation',

    defaults: {
        entity: '',
        className: '',
        id: '',
        name: ''
    },

    /**
     * @inheritdoc
     */
    constructor: function ActivityContextActivityModel(...args) {
        ActivityContextActivityModel.__super__.constructor.apply(this, args);
    },

    /**
     * @inheritdoc
     */
    initialize: function(data, options) {
        if (typeof options.route !== 'undefined') {
            this.route = options.route;
        }
        ActivityContextActivityModel.__super__.initialize.call(this, data, options);
    },

    /**
     * @inheritdoc
     */
    url: function() {
        const param = {
            activity: this.get('activityClassAlias'),
            id: this.get('entityId'),
            entity: this.get('targetClassName'),
            entityId: this.get('targetId')
        };

        return routing.generate(this.route, param);
    }
});

export default ActivityContextActivityModel;
