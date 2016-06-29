define(function(require) {
    'use strict';

    var CommentFormView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var formToAjaxOptions = require('oroui/js/tools/form-to-ajax-options');
    var BaseView = require('oroui/js/app/views/base/view');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');

    require('jquery.validate');

    function setValue($elem, value) {
        $elem.inputWidget('val', value);
        $elem.trigger('change');
    }

    CommentFormView = BaseView.extend({
        options: {
            messages: {
                deleteConfirmation: __('oro.comment.attachment.delete_confirmation')
            }
        },
        autoRender: true,
        events: {
            'submit': 'onSubmit',
            'reset': 'onReset',
            'click .remove-attachment': 'onRemoveAttachment'
        },

        listen: {
            'error model': 'onError'
        },

        initialize: function(options) {
            this.template = _.template($(options.template).html());
            CommentFormView.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            CommentFormView.__super__.render.call(this);

            this.$('form')
                .validate();
            this.initLayout();
            this.bindData();

            return this;
        },

        bindData: function() {
            var formView = this;
            var attrs = this.model.toJSON();
            _.each(attrs, function(value, name) {
                var $elem = formView.$('[name="' + name + '"]');
                if ($elem) {
                    setValue($elem, value);
                }
            });
        },

        /**
         * Fetches options with form-data to send it over ajax
         *
         * @param {Object=} options initial options
         * @returns {Object}
         */
        fetchAjaxOptions: function(options) {
            return formToAjaxOptions(this.$('form'), options);
        },

        /**
         * Update view after failed request
         *
         *  - shows error massages if they exist
         *
         * @param {Model} model
         * @param {Object} jqxhr
         */
        onError: function(model, jqxhr) {
            var validator;
            if (jqxhr.status === 400 && jqxhr.responseJSON && jqxhr.responseJSON.errors) {
                validator = this.$('form').data('validator');
                if (validator) {
                    validator.showBackendErrors(jqxhr.responseJSON.errors);
                }
            }
        },

        onSubmit: function(e) {
            e.stopPropagation();
            e.preventDefault();
            this.trigger('submit', this);
        },

        onReset: function(e) {
            e.stopPropagation();
            e.preventDefault();
            this.render();
        },

        onRemoveAttachment: function(e) {
            e.stopPropagation();
            e.preventDefault();
            this._confirmRemoveAttachment();
        },

        _confirmRemoveAttachment: function() {
            var confirm = new DeleteConfirmation({
                content: this._getMessage('deleteConfirmation')
            });
            confirm.on('ok', _.bind(this._removeAttachment, this));
            confirm.open();
        },

        _removeAttachment: function() {
            var itemView = this;
            this.model.removeAttachment().done(function() {
                itemView.$('.attachment-item').remove();
                mediator.execute('showFlashMessage', 'success', __('oro.comment.attachment.delete_message'));
            });
        },

        _getMessage: function(labelKey) {
            return this.options.messages[labelKey];
        }
    });

    return CommentFormView;
});
