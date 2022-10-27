define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view',
    'orotranslation/js/translator',
    'oroui/js/mediator', 'oroui/js/messenger',
    'oro/dialog-widget',
    'oroaddress/js/mapservice/googlemaps',
    'oroaddress/js/address/view',
    'oroaddress/js/address/collection',
    'oroui/js/delete-confirmation'
], function(
    $,
    _,
    BaseView,
    __,
    mediator,
    messenger,
    DialogWidget,
    Googlemaps,
    AddressView,
    AddressCollection,
    deleteConfirmation
) {
    'use strict';

    /**
     * @export  oroaddress/js/address-book
     * @class   oroaddress.AddressBook
     * @extends Backbone.View
     */
    const AddressBookView = BaseView.extend({
        isEmpty: false,

        options: {
            mapOptions: {
                zoom: 12
            },
            template: null,
            addressListUrl: null,
            addressCreateUrl: null,
            addressUpdateUrl: null,
            addressDeleteUrl: null,
            addressesContainerHtml: '<div class="map-address-list"></div>',
            noDataContainerHtml: '<div class="no-data"></div>',
            mapView: Googlemaps,
            addressMapOptions: {},
            addressTagName: 'div',
            allowToRemovePrimary: false,
            confirmRemove: true,
            confirmRemoveComponent: deleteConfirmation,
            showMap: true
        },
        noDataMessage: __('Empty Address Book'),
        attributes: {
            'class': 'map-box'
        },

        /**
         * @inheritdoc
         */
        constructor: function AddressBookView(options) {
            AddressBookView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.options.collection = this.options.collection || new AddressCollection();
            this.options.collection.url = this._getUrl('addressListUrl');

            this.listenTo(this.getCollection(), 'activeChange', this.activateAddress);
            this.listenTo(this.getCollection(), 'add', this.addAddress);
            this.listenTo(this.getCollection(), 'reset', this.addAll);
            this.listenTo(this.getCollection(), 'remove', this.onAddressRemove);

            this._initMainContainers();

            if (this.options.showMap) {
                this.initializeMap();
            }
        },

        initializeMap: function() {
            if (this.mapView) {
                return;
            }

            this.$mapContainerFrame = this.$el.find('.map-visual-frame');
            if (!this.$mapContainerFrame.length) {
                this.$mapContainerFrame = $('<div class="map-visual-frame"/>').appendTo(this.$el);
            }

            this.$mapContainerFrame.toggle(!this.isEmpty);

            this.mapView = new this.options.mapView({
                mapOptions: this.options.mapOptions,
                el: this.$mapContainerFrame
            });

            const activeAddress = this.getCollection().find({active: true});
            if (activeAddress) {
                this.activateAddress(activeAddress);
            }
        },

        disposeMap: function() {
            if (!this.mapView) {
                return;
            }

            this.$mapContainerFrame.remove();
            this.mapView.dispose();

            delete this.$mapContainerFrame;
            delete this.mapView;
        },

        _initMainContainers: function() {
            this.$noDataContainer = $(this.options.noDataContainerHtml).text(this.noDataMessage);
            this.$addressesContainer = $(this.options.addressesContainerHtml);

            if (!$.contains(this.$el[0], this.$addressesContainer[0])) {
                this.$el.append(this.$addressesContainer);
            }

            if (!$.contains(this.$el[0], this.$noDataContainer[0])) {
                this.$el.append(this.$noDataContainer);
            }
        },

        _getUrl: function(optionsKey, ...rest) {
            if (_.isFunction(this.options[optionsKey])) {
                return this.options[optionsKey].apply(this, rest);
            }
            return this.options[optionsKey];
        },

        getCollection: function() {
            return this.options.collection;
        },

        onAddressRemove: function() {
            if (!this.getCollection().where({active: true}).length) {
                const primaryAddress = this.getCollection().where({primary: true});
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
            this.$el.trigger('content:changed');
        },

        _hideEmptyMessage: function() {
            this.isEmpty = false;
            this.$noDataContainer.hide();
            if (this.$mapContainerFrame) {
                this.$mapContainerFrame.show();
            }
            this.$addressesContainer.show();
        },

        _showEmptyMessage: function() {
            this.isEmpty = true;
            this.$noDataContainer.show();
            if (this.$mapContainerFrame) {
                this.$mapContainerFrame.hide();
            }
            this.$addressesContainer.hide();
        },

        _activatePreviousAddress: function() {
            if (this.activeAddress !== undefined) {
                const previouslyActive = this.getCollection().where({id: this.activeAddress.get('id')});
                if (previouslyActive.length) {
                    previouslyActive[0].set('active', true);
                }
            }
        },

        addAddress: function(address) {
            if (!this.$el.find('#address-book-' + address.id).length) {
                const addressView = new AddressView({
                    model: address,
                    map: this.options.addressMapOptions,
                    template: this.options.template,
                    allowToRemovePrimary: this.options.allowToRemovePrimary,
                    confirmRemove: this.options.confirmRemove,
                    confirmRemoveComponent: this.options.confirmRemoveComponent,
                    addressDeleteUrl: this._getUrl('addressDeleteUrl', address),
                    tagName: this.options.addressTagName,
                    isAddressHtmlFormatted: this.options.isAddressHtmlFormatted
                });
                addressView.on('edit', this.editAddress.bind(this));
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
                    url: url,
                    title: title,
                    regionEnabled: false,
                    incrementalPosition: false,
                    dialogOptions: {
                        modal: true,
                        resizable: false,
                        width: 585,
                        autoResize: true,
                        close: () => {
                            delete this.addressEditDialog;
                        }
                    }
                });
                this.addressEditDialog.render();
                mediator.on('page:request', () => {
                    if (this.addressEditDialog) {
                        this.addressEditDialog.remove();
                    }
                });
                this.addressEditDialog.on('formSave', () => {
                    this.addressEditDialog.remove();
                    messenger.notificationFlashMessage('success', __('Address saved'));
                    this.reloadAddresses();
                });
            }
        },

        reloadAddresses: function() {
            this.getCollection().fetch({reset: true});
        },

        activateAddress: function(address) {
            if (!address.get('primary')) {
                this.activeAddress = address;
            }
            if (!this.mapView) {
                return;
            }
            this.mapView.updateMap(address.getSearchableString(), address.get('label'));
        }
    });

    return AddressBookView;
});
