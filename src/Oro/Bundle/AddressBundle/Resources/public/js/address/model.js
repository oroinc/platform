define([
    'backbone'
], function(Backbone) {
    'use strict';

    /**
     * @export  oroaddress/js/address/model
     * @class   oroaddress.address.Model
     * @extends Backbone.Model
     */
    const AddressModel = Backbone.Model.extend({
        defaults: {
            label: '',
            namePrefix: '',
            firstName: '',
            middleName: '',
            lastName: '',
            nameSuffix: '',
            organization: '',
            street: '',
            street2: '',
            city: '',
            country: '',
            countryIso2: '',
            countryIso3: '',
            postalCode: '',
            region: '',
            regionText: '',
            regionCode: '',
            primary: false,
            types: [],
            active: false
        },

        /**
         * @inheritdoc
         */
        constructor: function AddressModel(attrs, options) {
            AddressModel.__super__.constructor.call(this, attrs, options);
        },

        getSearchableString: function() {
            let address = '';

            if (this.get('country')) {
                address += this.get('country') + ', ';
            }
            if (this.get('region')) {
                address += this.get('region') + ', ';
            }
            if (this.get('city')) {
                address += this.get('city') + ', ';
            }
            if (this.get('street')) {
                address += this.get('street') + ' ';
            }
            if (this.get('street2')) {
                address += this.get('street2') + ' ';
            }
            if (this.get('postalCode')) {
                address += this.get('postalCode') + ', ';
            }

            return address;
        }
    });

    return AddressModel;
});
