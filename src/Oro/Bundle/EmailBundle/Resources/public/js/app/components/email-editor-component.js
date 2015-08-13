define(function(require) {
    'use strict';

    var EmailEditorComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var _ = require('underscore');
    var EmailEditorView = require('../views/email-editor-view');
    var emailTemplatesProvider = require('../../util/email-templates-provider');
    var EmailEditorModel = require('../models/email-editor-model');
    var EmailModel = require('../models/email-model');

    EmailEditorComponent = BaseComponent.extend({
        options: null,

        listen: {
            'parentResize': 'passResizeEvent'
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = options;
            this._deferredInit();
            this.view = new EmailEditorView({
                el: options._sourceElement,
                model: this.createEditorModelFromComponentOptions(options),
                templatesProvider: emailTemplatesProvider
            });
            this.view.render();
            this.view.renderPromise.done(_.bind(function() {
                this._resolveDeferredInit();
            }, this));
        },

        createEditorModelFromComponentOptions: function(options) {
            var $el = options._sourceElement;
            return new EmailEditorModel({
                appendSignature: options.appendSignature,
                isSignatureEditable: options.isSignatureEditable,
                signature: $el.find('[name$="[signature]"]').val(),
                email: new EmailModel({
                    subject: $el.find('[name$="[subject]"]').val(),
                    body: $el.find('[name$="[body]"]').val(),
                    type: $el.find('[name$="[type]"]').val(),
                    relatedEntityId: options.entityId,
                    parentEmailId: $el.find('[name$="[parentEmailId]"]').val(),
                    cc: options.cc,
                    bcc: options.bcc
                }),
                bodyFooter: $el.find('[name$="[bodyFooter]"]').val()
            });
        },

        passResizeEvent: function() {
            var component = this.view.pageComponent('wrap_oro_email_email_body');
            component.trigger('parentResize');
        }
    });
    return EmailEditorComponent;
});
