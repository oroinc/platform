import BaseComponent from 'oroui/js/app/components/base/component';
import _ from 'underscore';
import EmailEditorView from '../views/email-editor-view';
import emailTemplatesProvider from '../../util/email-templates-provider';
import EmailEditorModel from '../models/email-editor-model';
import EmailModel from '../models/email-model';

const EmailEditorComponent = BaseComponent.extend({
    options: {
        editorComponentName: 'oro_email_email_body'
    },

    listen: {
        parentResize: 'passResizeEvent'
    },

    /**
     * @inheritdoc
     */
    constructor: function EmailEditorComponent(options) {
        EmailEditorComponent.__super__.constructor.call(this, options);
    },

    /**
     * @constructor
     * @param {Object} options
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);
        this._deferredInit();
        this.view = new EmailEditorView({
            el: options._sourceElement,
            model: this.createEditorModelFromComponentOptions(options),
            templatesProvider: emailTemplatesProvider,
            editorComponentName: this.options.editorComponentName
        });
        this.view.render();
        this.view.renderPromise.then(() => {
            this._resolveDeferredInit();
        });
    },

    createEditorModelFromComponentOptions: function(options) {
        const $el = options._sourceElement;
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
        const component = this.view.pageComponent('wrap_' + this.options.editorComponentName);
        component.trigger('parentResize');
    }
});
export default EmailEditorComponent;
