define(function (require) {
    'use strict';

    var CommentFormView,
        HIDE_ERRORS_TIMEOUT = 3000, // 3 sec
        $ = require('jquery'),
        _ = require('underscore'),
        mediator = require('oroui/js/mediator'),
        formToAjaxOptions = require('oroui/js/tools/form-to-ajax-options'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
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
            'reset': 'onReset'
        },

        listen: {
            'request model': 'onRequest',
            'sync model': 'onSuccess',
            'error model': 'onError'
        },

        defaultData: {
            attachmentURL: null,
            attachmentFileName: null,
            attachmentSize: null
        },

        /**
         * Stores timeoutId for delayed hideErrors handler
         * @type {number}
         */
        hideErrorsTimeoutId: null,

        initialize: function (options) {
            this.template = _.template($(options.template ).html());
            this.isAddForm = this.model.isNew();
            if (this.isAddForm) {
                // save instance of empty model
                this.original = this.model.clone();
            }
            CommentFormView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            this._clearHideErrorsTimeout();
            CommentFormView.__super__.dispose.apply(this, arguments);
        },

        getTemplateData: function () {
            var data = CommentFormView.__super__.getTemplateData.call(this);
            _.defaults(data, this.defaultData);
            // id is required for template
            data.id = this.model.id || null;
            return data;
        },

        render: function () {
            var loading, self = this;

            CommentFormView.__super__.render.call(this);

            this.$('form')
                .addClass(this.isAddForm ? 'add-form' : 'edit-form')
                .validate({invalidHandler: function(event, validator) {
                    self.scheduleHideErrors(_.bind(validator.resetFormErrors, validator));
                }});
            mediator.execute('layout:init', this.$('form'), this);
            if (!this.isAddForm) {
                this.bindData();
            }

            loading = new LoadingMaskView({
                container: this.$el
            });
            this.subview('loading', loading);

            return this;
        },

        bindData: function () {
            var fromView = this,
                attrs = this.model.toJSON();
            _.each(attrs, function (value, name) {
                var $elem = fromView.$('[name="' + name + '"]');
                if ($elem) {
                    setValue($elem, value);
                }
            });
        },

        onSubmit: function (e) {
            e.stopPropagation();
            e.preventDefault();
            if (!this.subview('loading').isShown()) {
                this.trigger('submit', this);
            }
        },

        onReset: function (e) {
            e.stopPropagation();
            e.preventDefault();
            if (this.isAddForm) {
                this._clearForm();
            } else {
                this.bindData();
                this.trigger('reset', this);
            }
        },

        _elements: function () {
            return this.$('input, select, textarea').not(':submit, :reset, :image');
        },

        _clearForm: function () {
            this.stopListening();
            this.model = this.original.clone();
            this.delegateListeners();
            this._elements().each(function () {
                setValue($(this), '');
            });
        },

        /**
         * Schedule hideErrors handler
         *
         * @param {function} hideErrors
         */
        scheduleHideErrors: function (hideErrors) {
            this._clearHideErrorsTimeout();
            this.hideErrorsTimeoutId = _.delay(hideErrors, HIDE_ERRORS_TIMEOUT);
        },

        /**
         * Stops delayed hideErrors handler
         * @protected
         */
        _clearHideErrorsTimeout: function () {
            if (this.hideErrorsTimeoutId) {
                clearTimeout(this.hideErrorsTimeoutId);
                this.hideErrorsTimeoutId = null;
            }
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
         * Update view after request start
         *
         *  - shows loading mask
         */
        onRequest: function () {
            this.subview('loading').show();
        },

        /**
         * Update view after successful request
         *
         *  - hides loading mask
         *  - clears form if necessary
         */
        onSuccess: function () {
            this.subview('loading').hide();
            if (this.isAddForm) {
                this._clearForm();
            }
        },

        /**
         * Update view after failed request
         *
         *  - hides loading mask
         *  - shows error massages if they exist
         *
         * @param {Model} model
         * @param {Object} jqxhr
         */
        onError: function (model, jqxhr) {
            var validator;
            this.subview('loading').hide();
            if (jqxhr.status === 400 && jqxhr.responseJSON && jqxhr.responseJSON.errors) {
                validator = this.$('form').data('validator');
                if (validator) {
                    validator.showBackendErrors(jqxhr.responseJSON.errors);
                    this.scheduleHideErrors(_.bind(validator.resetFormErrors, validator));
                }
            }
        }
    });

    return CommentFormView;
});
