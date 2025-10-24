import mediator from 'oroui/js/mediator';
import BaseNavigationModel from 'oronavigation/js/app/models/base/model';
import BaseCollection from 'oroui/js/app/models/base/collection';

const BaseNavigationItemCollection = BaseCollection.extend({
    model: BaseNavigationModel,

    /**
     * @inheritdoc
     */
    constructor: function BaseNavigationItemCollection(...args) {
        BaseNavigationItemCollection.__super__.constructor.apply(this, args);
    },

    getCurrentModel() {
        return this.find(model => {
            return model.get('url') !== null && mediator.execute('compareUrl', model.get('url'));
        });
    }
});

export default BaseNavigationItemCollection;
