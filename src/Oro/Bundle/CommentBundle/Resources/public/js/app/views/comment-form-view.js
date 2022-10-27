define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const formToAjaxOptions = require('oroui/js/tools/form-to-ajax-options');
    const BaseView = require('oroui/js/app/views/base/view');
    const DeleteConfirmation = require('oroui/js/delete-confirmation');

    require('jquery.validate');

    function setValue($elem, value) {
        $elem.inputWidget('val', value);
        $elem.trigger('change');
    }

    const CommentFormView = BaseView.extend({
        options: {
            messages: {
                deleteConfirmation: __('oro.comment.attachment.delete_confirmation')
            }
        },
        autoRender: true,
        events: {
            'submit': 'onSubmit',
            'reset': 'onReset',
            'click [data-role="remove"]': 'onRemoveAttachment'
        },

        listen: {
            'error model': 'onError'
        },

        /**
         * @inheritdoc
         */
        constructor: function CommentFormView(options) {
            CommentFormView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            this.template = _.template($(options.template).html());
            CommentFormView.__super__.initialize.call(this, options);
        },

        render: function() {
            this._deferredRender();
            CommentFormView.__super__.render.call(this);

            this.$('form')
                .validate();
            this.initLayout().then(() => {
                this._resolveDeferredRender();
            });
            this.bindData();

            return this;
        },

        bindData: function() {
            const formView = this;
            const attrs = this.model.toJSON();
            _.each(attrs, function(value, name) {
                const $elem = formView.$('[name="' + name + '"]');
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
            let validator;
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
            const confirm = new DeleteConfirmation({
                content: this._getMessage('deleteConfirmation')
            });
            confirm.on('ok', this._removeAttachment.bind(this));
            confirm.open();
        },

        _removeAttachment: function() {
            const itemView = this;
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
