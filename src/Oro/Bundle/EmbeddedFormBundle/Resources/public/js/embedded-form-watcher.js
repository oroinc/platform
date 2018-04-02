define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Backbone = require('backbone');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');

    var EmbeddedFormWatcher;
    var $formTypeField;
    var $cssField;
    var $successMessageField;

    var rememberedFormType;
    var rememberedCss;
    var rememberedSuccessMessage;

    var blockNextRequest;

    function isFormStateChanged(currentCss, currentSuccessMessage) {
        return !(currentCss === rememberedCss && currentSuccessMessage === rememberedSuccessMessage);
    }

    function processFormTypeChange() {
        if (blockNextRequest) {
            blockNextRequest = false;
            return;
        }
        var currentFormType = $formTypeField.val();

        if (!isFormStateChanged($cssField.val(), $successMessageField.val())) {
            requestDefaultFormTypeData(currentFormType);

            return;
        }

        var confirm = new DeleteConfirmation({
            title: __('embedded_form.confirm_box.title'),
            okText: __('embedded_form.confirm_box.ok_text'),
            content: __('embedded_form.confirm_box.content')
        });
        confirm.on('ok', function() {
            requestDefaultFormTypeData(currentFormType);
        });
        confirm.on('cancel', function() {
            blockNextRequest = true;
            $formTypeField.val(rememberedFormType).trigger('change');
        });
        confirm.open();
    }

    function requestDefaultFormTypeData(formType) {
        if (!formType) {
            return;
        }

        mediator.execute('showLoading');
        var url = routing.generate('oro_embedded_form_default_data', {formType: formType});
        $.get(url)
            .done(function(data, code, response) {
                $cssField.val(data.css);
                $successMessageField.val(data.successMessage);

                rememberedCss = data.css;
                rememberedSuccessMessage = data.successMessage;
                rememberedFormType = formType;
            }).always(function() {
                mediator.execute('hideLoading');
            });
    }

    EmbeddedFormWatcher = Backbone.View.extend({
        /**
         * @inheritDoc
         */
        constructor: function EmbeddedFormWatcher() {
            EmbeddedFormWatcher.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (!_.isObject(options)) {
                return;
            }

            $formTypeField = $(options.formTypeFieldId);
            $cssField = $(options.cssFieldId);
            $successMessageField = $(options.successMessageFieldId);

            rememberedFormType = $formTypeField.val();
            rememberedCss = options.defaultCss;
            rememberedSuccessMessage = options.defaultSuccessMessage;

            blockNextRequest = false;

            this.startWatching(options.forceDataLoading);
        },

        startWatching: function(forceDataLoading) {
            $formTypeField.change(processFormTypeChange);

            if (forceDataLoading) {
                $formTypeField.trigger('change');
            }
        }
    });

    return EmbeddedFormWatcher;
});
