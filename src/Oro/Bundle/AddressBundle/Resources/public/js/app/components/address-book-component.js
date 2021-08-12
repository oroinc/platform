define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const AddressBookView = require('oroaddress/js/address-book');

    const AddressBookComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat([
            'addressListUrl', 'addressBookSelector', 'addresses', 'addressMapOptions', 'isAddressHtmlFormatted'
        ]),

        addressBookSelector: '#address-book',

        isAddressHtmlFormatted: false,

        /**
         * @inheritdoc
         */
        constructor: function AddressBookComponent(options) {
            AddressBookComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
                addressMapOptions: this.addressMapOptions || {},
                isAddressHtmlFormatted: this.isAddressHtmlFormatted
            };
        },

        _initializeAddressBook: function(addressBookViewOptions) {
            return new AddressBookView(addressBookViewOptions);
        }
    });

    return AddressBookComponent;
});
