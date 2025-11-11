import _ from 'underscore';
import RoutingCollection from 'oroui/js/app/models/base/routing-collection';

const AccessLevelsCollection = RoutingCollection.extend({
    routeDefaults: {
        routeName: 'oro_security_access_levels'
    },

    /**
     * @inheritdoc
     */
    constructor: function AccessLevelsCollection(...args) {
        AccessLevelsCollection.__super__.constructor.apply(this, args);
    },

    parse: function(resp, options) {
        return _.map(_.pairs(resp), function(item) {
            return {access_level: parseInt(item[0], 10), access_level_label: item[1]};
        });
    },

    getRouteName: function() {
        return this._route.get('routeName');
    }
});

export default AccessLevelsCollection;
