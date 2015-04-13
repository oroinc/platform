define(function (require) {
    'use strict';

    var CommentFormView,
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator'),
        formToAjaxOptions = require('oroui/js/tools/form-to-ajax-options'),
        BaseView = require('oroui/js/app/views/base/view');
    require('jquery.validate');

    function setValue($elem, value) {
        if ($elem.data('select2')) {
            $elem.select2('val', value);
        } else {
            $elem.val(value);
        }
        $elem.trigger('change');
    }

    CommentFormView = BaseView.extend({
        autoRender: true,
        events: {
            'submit': 'onSubmit',
            'reset': 'onReset',
            'click .remove-attachment': 'removeAttachment'
        },

        listen: {
            'error model': 'onError'
        },

        initialize: function (options) {
            this.template = _.template($(options.template).html());
            CommentFormView.__super__.initialize.apply(this, arguments);
        },

        render: function () {
            CommentFormView.__super__.render.call(this);

            this.$('form')
                .validate();
            mediator.execute('layout:init', this.$('form'), this);
            this.bindData();

            return this;
        },

        bindData: function () {
            var formView = this,
                attrs = this.model.toJSON();
            _.each(attrs, function (value, name) {
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
        fetchAjaxOptions: function (options) {
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
        onError: function (model, jqxhr) {
            var validator;
            if (jqxhr.status === 400 && jqxhr.responseJSON && jqxhr.responseJSON.errors) {
                validator = this.$('form').data('validator');
                if (validator) {
                    validator.showBackendErrors(jqxhr.responseJSON.errors);
                }
            }
        },

        onSubmit: function (e) {
            e.stopPropagation();
            e.preventDefault();
            this.trigger('submit', this);
        },

        onReset: function (e) {
            e.stopPropagation();
            e.preventDefault();
            this.render();
        },

        removeAttachment: function (e) {
            var itemView = this;
            e.stopPropagation();
            e.preventDefault();
            this.model.removeAttachment().done(function () {
                itemView.$('.attachment-item').remove();
                mediator.execute('showFlashMessage', 'success', __('oro.comment.attachment.delete_message'));
            });
        }
    });

    return CommentFormView;
});
