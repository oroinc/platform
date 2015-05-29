/*global define*/
define(function (require) {
    'use strict';

    var EmailEditorComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        _ = require('underscore'),
        EmailEditorView = require('../views/email-editor-view'),
        EmailEditorUtil = require('../../util/email-editor-util');

    EmailEditorComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            this._deferredInit();
            this.view = new EmailEditorView({
                el: options._sourceElement,
                model: EmailEditorUtil.readEmailEditorModel(options)
            });
            this.view.readyPromise.done(_.bind(this._resolveDeferredInit, this));
        }
    });
    return EmailEditorComponent;
});
