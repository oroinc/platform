define([
    'backbone'
], function(Backbone) {
    'use strict';

    /**
     * @export  oroaddress/js/region/model
     * @class   oroaddress.region.Model
     * @extends Backbone.Model
     */
    const AddressRegionModel = Backbone.Model.extend({
        defaults: {
            country: '',
            code: '',
            name: ''
        },

        /**
         * @inheritdoc
         */
        constructor: function AddressRegionModel(attrs, options) {
            AddressRegionModel.__super__.constructor.call(this, attrs, options);
        }
    });

    return AddressRegionModel;
});
