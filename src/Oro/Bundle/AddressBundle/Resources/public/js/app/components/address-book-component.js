define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const AddressBookView = require('oroaddress/js/address-book');

    const AddressBookComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat([
            'addressListUrl', 'addressBookSelector', 'addresses', 'addressMapOptions'
        ]),

        addressBookSelector: '#address-book',

        /**
         * @inheritDoc
         */
        constructor: function AddressBookComponent(options) {
            AddressBookComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this._createAddressBook();
        },

        _createAddressBook: function() {
            this.view = this._initializeAddressBook(this._getAddressBookViewOptions());

            this.view
                .getCollection()
                .reset(JSON.parse(this.addresses));
        },

        _getAddressBookViewOptions: function() {
            return {
                el: this.addressBookSelector,
                addressListUrl: this.addressListUrl,
                addressMapOptions: this.addressMapOptions || {}
            };
        },

        _initializeAddressBook: function(addressBookViewOptions) {
            return new AddressBookView(addressBookViewOptions);
        }
    });

    return AddressBookComponent;
});
