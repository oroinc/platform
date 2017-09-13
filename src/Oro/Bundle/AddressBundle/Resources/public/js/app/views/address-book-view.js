define(function(require) {
    'use strict';

    var AddressBookView;
    var BaseView = require('oroui/js/app/views/base/view');
    var AddressBook = require('oroaddress/js/address-book');
    var widgetManager = require('oroui/js/widget-manager');
    var routing = require('routing');
    var _ = require('underscore');

    AddressBookView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'addressListUrl', 'addressCreateUrl', 'addressUpdateRoute', 'addressDeleteRoute', 'addresses', 'wid'
        ]),

        addressBookEl: '#address-book',

        initialize: function() {
            this.addressBook = this._initializeAddressBook();

            this.addressBook
                .getCollection()
                .reset(JSON.parse(this.addresses));

            widgetManager.getWidgetInstance(this.wid, this._onWidgetLoad.bind(this));
        },

        _onWidgetLoad: function(widget) {
            widget.getAction('add_address', 'adopted', function(action) {
                var addressBook = this.addressBook;
                action.on('click', _.bind(addressBook.createAddress, addressBook));
            }.bind(this));
        },

        _initializeAddressBook: function() {
            var addressDeleteRoute = this.addressDeleteRoute;
            var addressUpdateRoute = this.addressUpdateRoute;

            return new AddressBook({
                el: this.addressBookEl,
                addressListUrl: this.addressListUrl,
                addressCreateUrl: this.addressCreateUrl,
                addressUpdateUrl: function() {
                    var address = arguments[0];

                    return routing.generate(
                        addressUpdateRoute.route,
                        _.extend({id: address.get('id')}, addressUpdateRoute.params)
                    );
                },
                addressDeleteUrl: function() {
                    var address = arguments[0];

                    return routing.generate(
                        addressDeleteRoute.route,
                        _.extend({addressId: address.get('id')}, addressDeleteRoute.params)
                    );
                }
            });
        }
    });

    return AddressBookView;
});
