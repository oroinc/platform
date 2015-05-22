/* global define */
define(function(require) {
    'use strict';

    var AttributeFormOptionRowView,
        _ = require('underscore'),
        $ = require('jquery'),
        __ = require('orotranslation/js/translator'),
        BaseView = require('oroui/js/app/views/base/view'),
        Confirmation = require('oroui/js/delete-confirmation');

    AttributeFormOptionRowView = BaseView.extend({
        tagName: 'tr',

        events: {
            'click .delete-form-option': 'triggerRemove',
            'click .edit-form-option': 'triggerEdit'
        },

        options: {
            workflow: null,
            template: null,
            data: {
                'label': null,
                'property_path': null,
                'required': false
            }
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            var template = this.options.template || $('#attribute-form-option-row-template').html();
            this.template = _.template(template);
            this.options.data.view_id = this.cid;
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
            confirm.on('ok', _.bind(function () {
                this.trigger('removeFormOption', this.options.data);
                this.remove();
            }, this));
            confirm.open();
        },

        render: function() {
            var rowHtml = $(this.template(this.options.data));
            this.$el.html(rowHtml);

            return this;
        }
    });

    return AttributeFormOptionRowView;
});
