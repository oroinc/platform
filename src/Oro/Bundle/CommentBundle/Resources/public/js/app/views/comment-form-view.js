/*jslint browser:true, nomen:true*/
/*global define, alert*/
define(function (require) {
    'use strict';

    var CommentFormView,
        $ = require('jquery'),
        _ = require('underscore'),
        tools = require('oroui/js/tools'),
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
            var attrs, options;
            e.stopPropagation();
            e.preventDefault();
            attrs = tools.unpackFromQueryString(this.$('form').serialize());
            options = formToAjaxOptions(this.$('form'));
            if (this.model) {
                attrs.id = this.model.id;
            }
            options.success = _.bind(this.onSuccessResponse, this);
            options.error = _.bind(this.onErrorResponse, this);
            this.trigger('submit', attrs, options);
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

        onSuccessResponse: function () {
            if (!this.model) {
                this._clearFrom();
            }
        },

        onErrorResponse: function (model, jqxhr, options) {
            if (jqxhr.status === 400 &&
                jqxhr.responseJSON && jqxhr.responseJSON.errors) {
                this.$('form').data('validator').showBackendErrors(jqxhr.responseJSON.errors);
            }
        }
    });

    return CommentFormView;
});
