/*global define*/
define(function (require) {
    'use strict';

    var EmailEditorComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        _ = require('underscore'),
        EmailEditorView = require('../views/email-editor-view'),
        emailEditorModelProvider = require('../../util/email-editor-model-provider'),
        emailTemplatesProvider = require('../../util/email-templates-provider');

    EmailEditorComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            this._deferredInit();
            this.view = new EmailEditorView({
                el: options._sourceElement,
                model: emailEditorModelProvider.createFromComponentOptions(options),
                templatesProvider: emailTemplatesProvider
            });
            this.view.readyPromise.done(_.bind(this._resolveDeferredInit, this));
        }
    });
    return EmailEditorComponent;
});
