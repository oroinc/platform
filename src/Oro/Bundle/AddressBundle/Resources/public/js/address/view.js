define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');
    const addressFormatter = require('orolocale/js/formatter/address');
    const deleteConfirmation = require('oroui/js/delete-confirmation');
    const __ = require('orotranslation/js/translator');
    const loadModules = require('oroui/js/app/services/load-modules');

    /**
     * @export  oroaddress/js/address/view
     * @class   oroaddress.address.View
     * @extends Backbone.View
     */
    const AddressView = BaseView.extend({
        tagName: 'div',

        attributes: {
            'class': 'map-item'
        },

        confirmRemoveComponent: deleteConfirmation,

        confirmRemoveMessages: {
            title: __('Delete Confirmation'),
            content: __('Are you sure you want to delete this item?'),
            okText: __('Yes, Delete'),
            cancelText: __('Cancel')
        },

        events: {
            'click': 'activate',
            'click .item-edit-button': 'edit',
            'click .item-remove-button': 'close'
        },

        defaultMapping: {
            namePrefix: 'prefix',
            nameSuffix: 'suffix',
            firstName: 'first_name',
            middleName: 'middle_name',
            lastName: 'last_name',
            organization: 'organization',
            street: 'street',
            street2: 'street2',
            city: 'city',
            country: 'country',
            countryIso2: 'country_iso2',
            countryIso3: 'country_iso3',
            postalCode: 'postal_code',
            region: 'region',
            regionText: 'region',
            regionCode: 'region_code',
            phone: 'phone'
        },

        options: {
            map: {},
            allowToRemovePrimary: false,
            confirmRemove: false,
            addressDeleteUrl: null,
            isAddressHtmlFormatted: false
        },

        /**
         * @inheritdoc
         */
        constructor: function AddressView(options) {
            AddressView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.mapping = _.extend({}, this.defaultMapping, this.options.map || {});
            if (this.options.confirmRemoveComponent) {
                if (_.isString(this.options.confirmRemoveComponent)) {
                    this.confirmRemoveComponentPromise = loadModules(this.options.confirmRemoveComponent);
                } else {
                    this.confirmRemoveComponent = this.options.confirmRemoveComponent;
                }
            }

            this.$el.attr('id', 'address-book-' + this.model.id);
            this.template = _.template($(options.template || '#template-addressbook-item').html());
            this.listenTo(this.model, 'destroy', this.dispose);
            this.listenTo(this.model, 'change:active', this.toggleActive);
        },

        dispose: function(options) {
            if (this.disposed) {
                return;
            }

            delete this.confirmRemoveComponent;
            return AddressView.__super__.dispose.call(this);
        },

        activate: function() {
            this.model.set('active', true);
        },

        toggleActive: function() {
            if (this.model.get('active')) {
                this.$el.addClass('active');
            } else {
                this.$el.removeClass('active');
            }
        },

        edit: function() {
            this.trigger('edit', this, this.model);
        },

        close: function() {
            if (this.model.get('primary') && !this.options.allowToRemovePrimary) {
                mediator.execute('showErrorMessage', __('Primary address can not be removed'));
            } else {
                this.confirmClose(this.model.destroy.bind(this.model, {
                    url: this.options.addressDeleteUrl,
                    wait: true
                }));
            }
        },

        confirmClose: function(callback) {
            if (this.options.confirmRemove) {
                if (this.confirmRemoveComponentPromise) {
                    this.confirmRemoveComponentPromise.then(function(confirmRemoveComponent) {
                        if (this.disposed) {
                            return;
                        }

                        const confirmRemoveView = new this.confirmRemoveComponent(this.confirmRemoveMessages);

                        this.subview('confirmRemoveView', confirmRemoveView);
                        confirmRemoveView.on('ok', callback)
                            .open();
                        this.confirmRemoveComponent = confirmRemoveComponent;
                        delete this.confirmRemoveComponentPromise;
                    }.bind(this));
                } else {
                    let confirmRemoveView = this.subview('confirmRemoveView');

                    if (confirmRemoveView === void 0 || confirmRemoveView.disposed) {
                        confirmRemoveView = new this.confirmRemoveComponent(this.confirmRemoveMessages);
                        this.subview('confirmRemoveView', confirmRemoveView);
                    } else {
                        confirmRemoveView.off('ok');
                    }

                    confirmRemoveView.on('ok', callback)
                        .open();
                }
            } else {
                callback();
            }
        },

        render: function() {
            const isAddressHtmlFormatted = this.options.isAddressHtmlFormatted;
            const data = this.model.toJSON();
            const mappedData = this.prepareData(data);
            data.formatted_address = addressFormatter.format(mappedData, null, '\n', isAddressHtmlFormatted);
            data.searchable_string = this.model.getSearchableString();
            data.isAddressHtmlFormatted = isAddressHtmlFormatted;
            this.$el.append(this.template(data));
            if (this.model.get('primary')) {
                this.activate();
            }
            return this;
        },

        prepareData: function(data) {
            const mappedData = {};
            const map = this.mapping;

            if (data) {
                _.each(data, function(value, key) {
                    if (map[key]) {
                        mappedData[map[key]] = mappedData[map[key]] || value;
                    }
                });
            }

            return mappedData;
        }
    });

    return AddressView;
});
