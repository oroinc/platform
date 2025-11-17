import Backbone from 'backbone';
import Model from './model';

/**
 * @class   orodashboard.items.Collection
 * @extends Backbone.Collection
 */
const DashboardItemsCollection = Backbone.Collection.extend({
    model: Model,

    /**
     * @inheritdoc
     */
    constructor: function DashboardItemsCollection(...args) {
        DashboardItemsCollection.__super__.constructor.apply(this, args);
    }
});

export default DashboardItemsCollection;
