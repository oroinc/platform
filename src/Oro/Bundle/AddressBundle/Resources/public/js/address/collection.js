define([
    'underscore',
    'backbone',
    'oroaddress/js/address/model'
], function(_, Backbone, AddressModel) {
    'use strict';

    var AddressCollection;

    /**
     * @export  oroaddress/js/address/collection
     * @class   oroaddress.address.Collection
     * @extends Backbone.Collection
     */
    AddressCollection = Backbone.Collection.extend({
        model: AddressModel,

        /**
         * @inheritDoc
         */
        constructor: function AddressCollection() {
            AddressCollection.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.on('change:active', this.onActiveChange, this);
        },

        onActiveChange: function(item) {
            // Only 1 item allowed to be active
            if (item.get('active')) {
                var activeItems = this.where({active: true});
                _.each(activeItems, function(activeItem) {
                    if (activeItem.get('id') !== item.get('id')) {
                        activeItem.set('active', false);
                    }
                });
                this.trigger('activeChange', item);
            }
        }
    });

    return AddressCollection;
});
