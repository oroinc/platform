define(function(require) {
    'use strict';

    var AttributeFormOptionRowView;
    var _ = require('underscore');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var BaseView = require('oroui/js/app/views/base/view');
    var Confirmation = require('oroui/js/delete-confirmation');

    AttributeFormOptionRowView = BaseView.extend({
        tagName: 'tr',

        events: {
            'click .delete-form-option': 'triggerRemove',
            'click .edit-form-option': 'triggerEdit'
        },

        options: {
            template: null,
            data: {
                label: null,
                property_path: null,
                required: false
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function AttributeFormOptionRowView() {
            AttributeFormOptionRowView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            var template = this.options.template || $('#attribute-form-option-row-template').html();
            this.template = _.template(template);
        },

        update: function(data) {
            this.options.data = data;
            this.render();
        },

        triggerEdit: function(e) {
            e.preventDefault();
            this.trigger('editFormOption', this.options.data);
        },

        triggerRemove: function(e) {
            e.preventDefault();

            var confirm = new Confirmation({
                content: __('Are you sure you want to delete this field?')
            });
            confirm.on('ok', _.bind(function() {
                this.trigger('removeFormOption', this.options.data);
            }, this));
            confirm.open();
        },

        getTemplateData: function() {
            return this.options.data;
        }
    });

    return AttributeFormOptionRowView;
});
