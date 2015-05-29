/*global define*/
define(function (require) {
    'use strict';

    var EmailEditorComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        _ = require('underscore'),
        EmailEditorView = require('../views/email-editor-view'),
        emailEditorUtil = require('../../util/email-editor-util'),
        emailTemplateGenerator = require('../../util/email-templates-generator');

    EmailEditorComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            this._deferredInit();
            this.view = new EmailEditorView({
                el: options._sourceElement,
                model: emailEditorUtil.readEmailEditorModel(options),
                templateGenerator: emailTemplateGenerator
            });
            this.view.readyPromise.done(_.bind(this._resolveDeferredInit, this));
        }
    });
    return EmailEditorComponent;
});
