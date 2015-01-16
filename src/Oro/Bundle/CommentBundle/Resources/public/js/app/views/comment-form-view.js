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

        defaultData: {
            attachmentURL: null,
            attachmentFileName: null,
            attachmentSize: null
        },

        initialize: function (options) {
            this.template = _.template($(options.template ).html());
            CommentFormView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function () {
            var data = CommentFormView.__super__.getTemplateData.call(this);
            _.defaults(data, this.defaultData);
            // id is required for template
            data.id = this.model ? this.model.get('id') : null;
            return data;
        },

        render: function () {
            var loading;

            CommentFormView.__super__.render.call(this);

            this.$('form')
                .addClass(this.model ? 'edit-form' : 'add-form')
                .validate({invalidHandler: function(event, validator) {
                    _.delay(_.bind(validator.resetForm, validator), 3000);
                }});
            mediator.execute('layout:init', this.$('form'));
            if (this.model) {
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
            if (this.model) {
                this.trigger('reset', this.model);
            } else {
                this._clearFrom();
            }
        },

        _elements: function () {
            return this.$('input, select, textarea').not(':submit, :reset, :image');
        },

        _clearFrom: function () {
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
        requestStarted: function () {
            this.subview('loading').show();
        },

        /**
         * Update view after successful request
         *
         *  - hides loading mask
         *  - clears form if necessary
         */
        requestSucceeded: function () {
            this.subview('loading').hide();
            if (!this.model) {
                this._clearFrom();
            }
        },

        /**
         * Update view after failed request
         *
         *  - hides loading mask
         *  - shows error massages if they exist
         *
         * @param {Object} errors
         */
        requestFailed: function (errors) {
            this.subview('loading').hide();
            if (errors) {
                this.$('form').data('validator').showBackendErrors(errors);
            }
        }
    });

    return CommentFormView;
});
