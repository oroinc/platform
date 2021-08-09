define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const Backbone = require('backbone');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const DeleteConfirmation = require('oroui/js/delete-confirmation');

    let $formTypeField;
    let $cssField;
    let $successMessageField;

    let rememberedFormType;
    let rememberedCss;
    let rememberedSuccessMessage;

    let blockNextRequest;

    function isFormStateChanged(currentCss, currentSuccessMessage) {
        return !(currentCss === rememberedCss && currentSuccessMessage === rememberedSuccessMessage);
    }

    function processFormTypeChange() {
        if (blockNextRequest) {
            blockNextRequest = false;
            return;
        }
        const currentFormType = $formTypeField.val();

        if (!isFormStateChanged($cssField.val(), $successMessageField.val())) {
            requestDefaultFormTypeData(currentFormType);

            return;
        }

        const confirm = new DeleteConfirmation({
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
        const url = routing.generate('oro_embedded_form_default_data', {formType: formType.replace('\\', '_')});
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

    const EmbeddedFormWatcher = Backbone.View.extend({
        /**
         * @inheritdoc
         */
        constructor: function EmbeddedFormWatcher(options) {
            EmbeddedFormWatcher.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
