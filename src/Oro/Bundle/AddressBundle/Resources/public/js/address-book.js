define([
    'underscore',
    'backbone',
    'orotranslation/js/translator',
    'oroui/js/mediator', 'oroui/js/messenger',
    'oro/dialog-widget',
    'oroaddress/js/mapservice/googlemaps',
    'oroaddress/js/address/view',
    'oroaddress/js/address/collection'
], function(
    _,
    Backbone,
    __,
    mediator,
    messenger,
    DialogWidget,
    Googlemaps,
    AddressView,
    AddressCollection
) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oroaddress/js/address-book
     * @class   oroaddress.AddressBook
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        options: {
            'mapOptions': {
                zoom: 12
            },
            'template': null,
            'addressListUrl': null,
            'addressCreateUrl': null,
            'addressUpdateUrl': null,
            'mapView': Googlemaps,
            'addressMapOptions': {},
            'allowToRemovePrimary': false,
            'confirmRemove': false,
            'confirmRemoveComponent': null
        },
        noDataMessage: __('Empty Address Book'),
        attributes: {
            'class': 'list-box map-box'
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.options.collection = this.options.collection || new AddressCollection();
            this.options.collection.url = this._getUrl('addressListUrl');

            this.listenTo(this.getCollection(), 'activeChange', this.activateAddress);
            this.listenTo(this.getCollection(), 'add', this.addAddress);
            this.listenTo(this.getCollection(), 'reset', this.addAll);
            this.listenTo(this.getCollection(), 'remove', this.onAddressRemove);

            this._initMainContainers();

            this.mapView = new this.options.mapView({
                'mapOptions': this.options.mapOptions,
                'el': this.$mapContainerFrame
            });
        },

        _initMainContainers: function() {
            this.$noDataContainer = $('<div class="no-data"><span>' + this.noDataMessage + '</span></div>');
            this.$addressesContainer = $('<div class="map-address-list"/>');
            this.$mapContainerFrame = $('<div class="map-visual-frame"/>');

            if (!this.$el.find('.map-address-list').length) {
                this.$el.append(this.$addressesContainer);
            }

            if (!this.$el.find('.map-visual-frame').length) {
                this.$el.append(this.$mapContainerFrame);
            }

            if (!this.$el.find('.no-data').length) {
                this.$el.append(this.$noDataContainer);
            }
        },

        _getUrl: function(optionsKey) {
            if (_.isFunction(this.options[optionsKey])) {
                return this.options[optionsKey].apply(this, Array.prototype.slice.call(arguments, 1));
            }
            return this.options[optionsKey];
        },

        getCollection: function() {
            return this.options.collection;
        },

        onAddressRemove: function() {
            if (!this.getCollection().where({active: true}).length) {
                var primaryAddress = this.getCollection().where({primary: true});
                if (primaryAddress.length) {
                    primaryAddress[0].set('active', true);
                } else if (this.getCollection().length) {
                    this._activateFirstAddress();
                }
            }
        },

        _activateFirstAddress: function() {
            this.getCollection().at(0).set('active', true);
        },

        addAll: function(items) {
            this.$addressesContainer.empty();
            if (items.length > 0) {
                this._hideEmptyMessage();
                items.each(function(item) {
                    this.addAddress(item);
                }, this);
            } else {
                this._showEmptyMessage();
            }
            if (items.length === 1) {
                this._activateFirstAddress();
            } else {
                this._activatePreviousAddress();
            }
        },

        _hideEmptyMessage: function() {
            this.$noDataContainer.hide();
            this.$mapContainerFrame.show();
            this.$addressesContainer.show();
        },

        _showEmptyMessage: function() {
            this.$noDataContainer.show();
            this.$mapContainerFrame.hide();
            this.$addressesContainer.hide();
        },

        _activatePreviousAddress: function() {
            if (this.activeAddress !== undefined) {
                var previouslyActive = this.getCollection().where({id: this.activeAddress.get('id')});
                if (previouslyActive.length) {
                    previouslyActive[0].set('active', true);
                }
            }
        },

        addAddress: function(address) {
            if (!this.$el.find('#address-book-' + address.id).length) {
                var addressView = new AddressView({
                    model: address,
                    map: this.options.addressMapOptions,
                    template: this.options.template,
                    allowToRemovePrimary: this.options.allowToRemovePrimary,
                    confirmRemove: this.options.confirmRemove,
                    confirmRemoveComponent: this.options.confirmRemoveComponent
                });
                addressView.on('edit', _.bind(this.editAddress, this));
                this.$addressesContainer.append(addressView.render().$el);
            }
        },

        editAddress: function(addressView, address) {
            this._openAddressEditForm(__('Update Address'), this._getUrl('addressUpdateUrl', address));
        },

        createAddress: function() {
            this._openAddressEditForm(__('Add Address'), this._getUrl('addressCreateUrl'));
        },

        _openAddressEditForm: function(title, url) {
            if (!this.addressEditDialog) {
                this.addressEditDialog = new DialogWidget({
                    'url': url,
                    'title': title,
                    'regionEnabled': false,
                    'incrementalPosition': false,
                    'dialogOptions': {
                        'modal': true,
                        'resizable': false,
                        'width': 475,
                        'autoResize': true,
                        'close': _.bind(function() {
                            delete this.addressEditDialog;
                        }, this)
                    }
                });
                this.addressEditDialog.render();
                mediator.on(
                    'page:request',
                    _.bind(function() {
                        if (this.addressEditDialog) {
                            this.addressEditDialog.remove();
                        }
                    }, this)
                );
                this.addressEditDialog.on('formSave', _.bind(function() {
                    this.addressEditDialog.remove();
                    messenger.notificationFlashMessage('success', __('Address saved'));
                    this.reloadAddresses();
                }, this));
            }
        },

        reloadAddresses: function() {
            this.getCollection().fetch({reset: true});
        },

        activateAddress: function(address) {
            if (!address.get('primary')) {
                this.activeAddress = address;
            }
            this.mapView.updateMap(address.getSearchableString(), address.get('label'));
        }
    });
});
