define([
    'underscore',
    'backbone',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    'orolocale/js/formatter/address',
    'oroaction/js/action-manager'
], function(_, Backbone, __, mediator, addressFormatter, ActionManager) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oroaddress/js/address/view
     * @class   oroaddress.address.View
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        tagName: 'div',

        attributes: {
            'class': 'list-item map-item'
        },

        messages: {
            title: __('Delete Confirmation'),
            message: __('Are you sure you want to delete this item?'),
            okText: __('Yes, Delete'),
            cancelText: __('Cancel')
        },

        events: {
            'click': 'activate',
            'click .item-edit-button': 'edit',
            'click .item-remove-button': 'close'
        },

        options: {
            map: {
                'namePrefix': 'prefix',
                'nameSuffix': 'suffix',
                'firstName': 'first_name',
                'middleNamem': 'iddle_name',
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
            }
        },

        initialize: function(options) {
            this.options.map = _.defaults(options.map || {}, this.options.map);
            this.options.allowToRemovePrimary = _.defaults(options.allowToRemovePrimary || {}, this.options.allowToRemovePrimary);
            this.options.confirmation = _.defaults(options.confirmation || {}, this.options.confirmation);
            this.$el.attr('id', 'address-book-' + this.model.id);
            this.template = _.template($(options.template || '#template-addressbook-item').html());
            this.listenTo(this.model, 'destroy', this.remove);
            this.listenTo(this.model, 'change:active', this.toggleActive);
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
                this.confirmationExecutor(_.bind(this.model.destroy, this.model, {wait: true}));
            }
        },

        confirmationExecutor: function(callback) {
            if (this.options.confirmation) {
                new ActionManager({confirmation: this.messages}).showConfirmDialog(callback);
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
            var map = this.options.map;

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
});
