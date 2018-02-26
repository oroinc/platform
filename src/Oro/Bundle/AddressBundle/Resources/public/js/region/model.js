define([
    'backbone'
], function(Backbone) {
    'use strict';

    var AddressRegionModel;
    /**
     * @export  oroaddress/js/region/model
     * @class   oroaddress.region.Model
     * @extends Backbone.Model
     */
    AddressRegionModel = Backbone.Model.extend({
        defaults: {
            country: '',
            code: '',
            name: ''
        },

        /**
         * @inheritDoc
         */
        constructor: function AddressRegionModel() {
            AddressRegionModel.__super__.constructor.apply(this, arguments);
        }
    });

    return AddressRegionModel;
});
