import Backbone from 'backbone';
import routing from 'routing';
import RegionModel from 'oroaddress/js/region/model';
import _ from 'underscore';

/**
 * @export  oroaddress/js/region/collection
 * @class   oroaddress.region.Collection
 * @extends Backbone.Collection
 */
const AddressRegionCollection = Backbone.Collection.extend({
    defaultOptions: {
        route: 'oro_api_country_get_regions'
    },
    url: null,
    model: RegionModel,

    /**
     * @inheritdoc
     */
    constructor: function AddressRegionCollection(...args) {
        AddressRegionCollection.__super__.constructor.apply(this, args);
    },

    /**
     * @inheritdoc
     */
    initialize: function(models, options) {
        this.options = _.extend({}, this.defaultOptions, options);
        this.url = routing.generate(this.options.route);
    },

    /**
     * Regenerate route for selected country
     *
     * @param id {string}
     */
    setCountryId: function(id) {
        this.url = routing.generate(this.options.route, {country: id});
    }
});

export default AddressRegionCollection;
