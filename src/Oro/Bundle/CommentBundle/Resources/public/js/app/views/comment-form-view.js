/*jslint browser:true, nomen:true*/
/*global define, alert*/
define(function (require) {
    'use strict';

    var CommentFormView,
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

        initialize: function (options) {
            this.template = _.template($(options.template ).html());
            this.isAddForm = this.model.isNew();
            if (this.isAddForm) {
                // save instance of empty model
                this.original = this.model.clone();
            }
            CommentFormView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function () {
            var data = CommentFormView.__super__.getTemplateData.call(this);
            _.defaults(data, this.defaultData);
            // id is required for template
            data.id = this.model.id || null;
            return data;
        },

        render: function () {
            var loading;

            CommentFormView.__super__.render.call(this);

            this.$('form')
                .addClass(this.isAddForm ? 'add-form' : 'edit-form')
                .validate({invalidHandler: function(event, validator) {
                    _.delay(_.bind(validator.resetFormErrors, validator), 3000);
                }});
            mediator.execute('layout:init', this.$('form'));
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
            this.trigger('submit', this);
        },

        onReset: function (e) {
            e.stopPropagation();
            e.preventDefault();
            if (this.isAddForm) {
                this._clearFrom();
            } else {
                this.bindData();
                this.trigger('reset', this);
            }
        },

        _elements: function () {
            return this.$('input, select, textarea').not(':submit, :reset, :image');
        },

        _clearFrom: function () {
            this.stopListening();
            this.model = this.original.clone();
            this.delegateListeners();
            this._elements().each(function () {
                setValue($(this), '');
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
                this._clearFrom();
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
            this.subview('loading').hide();
            if (jqxhr.status === 400 &&
                jqxhr.responseJSON && jqxhr.responseJSON.errors) {
                this.$('form').data('validator').showBackendErrors(jqxhr.responseJSON.errors);
            }
        }
    });

    return CommentFormView;
});
