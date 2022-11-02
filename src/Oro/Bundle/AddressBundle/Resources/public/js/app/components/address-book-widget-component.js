define(function(require) {
    'use strict';

    const AddressBookComponent = require('oroaddress/js/app/components/address-book-component');
    const widgetManager = require('oroui/js/widget-manager');
    const routing = require('routing');
    const _ = require('underscore');

    const AddressBookWidgetComponent = AddressBookComponent.extend({
        optionNames: AddressBookComponent.prototype.optionNames.concat([
            'wid', 'addressCreateUrl', 'addressUpdateRoute', 'addressDeleteRoute'
        ]),

        /**
         * @inheritdoc
         */
        constructor: function AddressBookWidgetComponent(options) {
            AddressBookWidgetComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            AddressBookWidgetComponent.__super__.initialize.call(this, options);

            widgetManager.getWidgetInstance(this.wid, this._onWidgetLoad.bind(this));
        },

        _getAddressBookViewOptions: function() {
            const addressDeleteRoute = this.addressDeleteRoute;
            const addressUpdateRoute = this.addressUpdateRoute;

            const options = AddressBookWidgetComponent.__super__._getAddressBookViewOptions.call(this);

            return _.extend(options, {
                addressCreateUrl: this.addressCreateUrl,
                addressUpdateUrl: function(address) {
                    return routing.generate(
                        addressUpdateRoute.route,
                        _.extend({id: address.get('id')}, addressUpdateRoute.params)
                    );
                },
                addressDeleteUrl: function(address) {
                    return routing.generate(
                        addressDeleteRoute.route,
                        _.extend({addressId: address.get('id')}, addressDeleteRoute.params)
                    );
                }
            });
        },

        _onWidgetLoad: function(widget) {
            widget.getAction('add_address', 'adopted', function(action) {
                const addressBookView = this.view;
                action.on('click', addressBookView.createAddress.bind(addressBookView));
            }.bind(this));
        }
    });

    return AddressBookWidgetComponent;
});
