/*global define*/
define(function (require) {
    'use strict';

    var EmailEditorComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        _ = require('underscore'),
        EmailEditorView = require('../views/email-editor-view'),
        emailTemplatesProvider = require('../../util/email-templates-provider'),
        EmailEditorModel = require('../models/email-editor-model'),
        EmailModel = require('../models/email-model'),
        DialogWidget = require('oro/dialog-widget');

    EmailEditorComponent = BaseComponent.extend({
        /**
         * margin of <div class="control-group">
         */
        CONTROL_GROUP_MARGIN: 10,

        listen: {
            'parentResize': 'onResize'
        },

        options: null,

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            this.options = options;
            this._deferredInit();
            this.view = new EmailEditorView({
                el: options._sourceElement,
                model: this.createEditorModelFromComponentOptions(options),
                templatesProvider: emailTemplatesProvider
            });
            this.view.render();
            this.view.renderPromise.done(_.bind(function(){
                this._resolveDeferredInit()
                this.listenTo(this.view.pageComponent('bodyEditor').view, 'resize', this.onResize, this);
            }, this));
        },

        createEditorModelFromComponentOptions: function (options) {
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

        onResize: function () {
            var outerHeight, innerHeight, editorHeight, availableHeight;
            outerHeight = this.view.$el.closest('.ui-widget-content').height();
            innerHeight = this.view.$el.height();
            editorHeight = this.view.pageComponent('bodyEditor').view.getHeight();
            availableHeight = Math.max(outerHeight - innerHeight + editorHeight - this.CONTROL_GROUP_MARGIN, this.options.minimalWysiwygEditorHeight);
            this.view.pageComponent('bodyEditor').view.setHeight(availableHeight);
        }
    });
    return EmailEditorComponent;
});
