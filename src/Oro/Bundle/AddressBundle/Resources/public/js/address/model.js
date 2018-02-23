define([
    'backbone'
], function(Backbone) {
    'use strict';

    var AddressModel;

    /**
     * @export  oroaddress/js/address/model
     * @class   oroaddress.address.Model
     * @extends Backbone.Model
     */
    AddressModel = Backbone.Model.extend({
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
         * @inheritDoc
         */
        constructor: function AddressModel() {
            AddressModel.__super__.constructor.apply(this, arguments);
        },

        getSearchableString: function() {
            var address = '';

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
