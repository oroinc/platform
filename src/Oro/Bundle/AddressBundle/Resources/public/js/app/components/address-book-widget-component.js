define(function(require) {
    'use strict';

    var AddressBookWidgetComponent;
    var AddressBookComponent = require('oroaddress/js/app/components/address-book-component');
    var widgetManager = require('oroui/js/widget-manager');
    var routing = require('routing');
    var _ = require('underscore');

    AddressBookWidgetComponent = AddressBookComponent.extend({
        optionNames: AddressBookComponent.prototype.optionNames.concat([
            'wid', 'addressCreateUrl', 'addressUpdateRoute', 'addressDeleteRoute'
        ]),

        /**
         * @inheritDoc
         */
        constructor: function AddressBookWidgetComponent() {
            AddressBookWidgetComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            AddressBookWidgetComponent.__super__.initialize.call(this, arguments);

            widgetManager.getWidgetInstance(this.wid, this._onWidgetLoad.bind(this));
        },

        _getAddressBookViewOptions: function() {
            var addressDeleteRoute = this.addressDeleteRoute;
            var addressUpdateRoute = this.addressUpdateRoute;

            return {
                el: this.addressBookSelector,
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
            };
        },

        _onWidgetLoad: function(widget) {
            widget.getAction('add_address', 'adopted', function(action) {
                var addressBookView = this.view;
                action.on('click', _.bind(addressBookView.createAddress, addressBookView));
            }.bind(this));
        }
    });

    return AddressBookWidgetComponent;
});
