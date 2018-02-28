define(function(require) {
    'use strict';

    var AddressBookComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var AddressBookView = require('oroaddress/js/address-book');

    AddressBookComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat([
            'addressListUrl', 'addressBookSelector', 'addresses'
        ]),

        addressBookSelector: '#address-book',

        /**
         * @inheritDoc
         */
        constructor: function AddressBookComponent() {
            AddressBookComponent.__super__.constructor.apply(this, arguments);
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
                addressListUrl: this.addressListUrl
            };
        },

        _initializeAddressBook: function(addressBookViewOptions) {
            return new AddressBookView(addressBookViewOptions);
        }
    });

    return AddressBookComponent;
});
