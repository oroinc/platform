define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view',
    'oroui/js/mediator',
    'orolocale/js/formatter/address',
    'oroui/js/delete-confirmation',
    'orotranslation/js/translator'
], function($, _, BaseView, mediator, addressFormatter, deleteConfirmation, __) {
    'use strict';

    var AddressView;

    /**
     * @export  oroaddress/js/address/view
     * @class   oroaddress.address.View
     * @extends Backbone.View
     */
    AddressView = BaseView.extend({
        tagName: 'div',

        attributes: {
            'class': 'list-item map-item'
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
            'namePrefix': 'prefix',
            'nameSuffix': 'suffix',
            'firstName': 'first_name',
            'middleName': 'middle_name',
            'lastName': 'last_name',
            'organization': 'organization',
            'street': 'street',
            'street2': 'street2',
            'city': 'city',
            'country': 'country',
            'countryIso2': 'country_iso2',
            'countryIso3': 'country_iso3',
            'postalCode': 'postal_code',
            'region': 'region',
            'regionText': 'region',
            'regionCode': 'region_code'
        },

        options: {
            map: {},
            'allowToRemovePrimary': false,
            'confirmRemove': false
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.mapping = _.extend({}, this.defaultMapping, this.options.map || {});
            if (this.options.confirmRemoveComponent) {
                this.confirmRemoveComponent = this.options.confirmRemoveComponent;
                if (_.isString(this.confirmRemoveComponent)) {
                    this.confirmRemoveComponent = require(this.confirmRemoveComponent);
                }
            }

            this.$el.attr('id', 'address-book-' + this.model.id);
            this.template = _.template($(options.template || '#template-addressbook-item').html());
            this.listenTo(this.model, 'destroy', this.remove);
            this.listenTo(this.model, 'change:active', this.toggleActive);
        },

        dispose: function(options) {
            if (this.disposed) {
                return;
            }

            delete this.confirmRemoveComponent;
            return AddressView.__super__.dispose.apply(this, arguments);
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
                this.confirmClose(_.bind(this.model.destroy, this.model, {wait: true}));
            }
        },

        confirmClose: function(callback) {
            if (this.options.confirmRemove) {
                var confirmRemoveComponent = new this.confirmRemoveComponent(this.confirmRemoveMessages);
                this.subview('confirmRemoveComponent', confirmRemoveComponent);
                confirmRemoveComponent.on('ok', callback)
                    .open();
            } else {
                callback();
            }
        },

        render: function() {
            var data = this.model.toJSON();
            var mappedData = this.prepareData(data);
            data.formatted_address = addressFormatter.format(mappedData, null, '\n');
            this.$el.append(this.template(data));
            if (this.model.get('primary')) {
                this.activate();
            }
            return this;
        },

        prepareData: function(data) {
            var mappedData = {};
            var map = this.mapping;

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
