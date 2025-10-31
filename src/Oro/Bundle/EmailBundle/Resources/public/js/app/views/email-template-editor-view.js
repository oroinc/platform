import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';
import DialogWidget from 'oro/dialog-widget';
import routing from 'routing';
import mediator from 'oroui/js/mediator';

const EmailTemplateEditorView = BaseView.extend({
    options: {
        typeSwitcher: 'input[name*="type"]', // type (Html or Plain) switcher selector
        hasWysiwyg: false, // is wysiwyg editor enabled in System->Configuration
        isWysiwygEnabled: false, // true if 'type' is set to 'Html'
        emailVariableView: {}, // link to app/views/email-variable-view
        getAttachmentsRoute: 'oro_email_emailtemplate_ajax_get_attachment_choices'
    },

    listen: {
        'email-variable-view:click-variable mediator': '_onVariableClick'
    },

    events: {
        'change input[name*=type]': '_onTypeChange',
        'change select[name*=entityName]': '_onEntityNameChange',
        'click .dialog-form-renderer': '_onPreview'
    },

    /**
     * @property {jQuery.Element|null}
     */
    $entityNameEl: null,

    /**
     * @property {Array|null}
     */
    attachmentChoices: null,

    /**
     * @inheritdoc
     */
    constructor: function EmailTemplateEditorView(options) {
        EmailTemplateEditorView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        EmailTemplateEditorView.__super__.initialize.call(this, options);

        this.options = _.defaults(options || {}, this.options);
        this.$entityNameEl = this.$el.find(':input[data-name="field__entity-name"]');

        this.render();
    },

    render: function() {
        this.initLayout({emailTemplateEditorView: this}).then(this.afterLayoutInit.bind(this));
    },

    afterLayoutInit: function() {
        this.options.hasWysiwyg = Boolean(this.$('textarea[name*="content"]:first').data('wysiwygEnabled'));
        if (this.options.hasWysiwyg) {
            this.options.isWysiwygEnabled = this.$(this.options.typeSwitcher).filter(':checked').val() === 'html';
            this.options.emailVariableView = this.pageComponent('email-template-variables');

            this._onEditorBlur();

            if (this.options.isWysiwygEnabled === false) {
                this._switchWysiwygEditor(false);
            }
        }
    },

    _onPreview: function(event) {
        event.preventDefault();
        const $currentView = this.$el;

        const iframeId = 'preview-frame';
        const iframe = $('<iframe />', {
            name: iframeId,
            id: iframeId,
            frameborder: 0,
            marginwidth: 20,
            marginheight: 20,
            allowfullscreen: true
        });

        const formAction = $currentView.attr('action');

        $currentView.one('submit', function(e) {
            if (!e.result) {
                return;
            }
            const confirmModal = new DialogWidget({
                title: __('Preview'),
                dialogOptions: {
                    modal: true,
                    resizable: true,
                    width: '85%',
                    height: '70%',
                    autoResize: true
                }
            });
            confirmModal.render();
            confirmModal._onContentLoad('<div class="widget-content"></div>');
            confirmModal._showLoading();
            confirmModal.widget.addClass('dialog-single-iframe-container');
            confirmModal.$el.append(iframe);
            $currentView.attr('target', iframeId);
            $currentView.attr('action', $(event.currentTarget).attr('href'));

            iframe.one('load', function() {
                confirmModal._hideLoading();
                $currentView.attr('target', null);
                $currentView.attr('action', formAction);
            });

            // prevent navigation form processing
            e.stopImmediatePropagation();
        });

        $currentView.trigger('submit');
    },

    _onVariableClick: function(field, value) {
        if (this.options.isWysiwygEnabled) {
            this.forEachComponent(function(component) {
                if (_.result(component.view, 'tinymceConnected') === true && component.view.$el.is(field)) {
                    component.view.tinymceInstance.execCommand('mceInsertContent', false, value);
                    component.view.tinymceInstance.execCommand('mceFocus', false, value);
                }
            });
        }
    },

    _onEditorBlur: function() {
        if (this.options.hasWysiwyg && this.options.isWysiwygEnabled) {
            this.forEachComponent(function(component) {
                if (_.result(component.view, 'tinymceConnected') === true &&
                    !_.isNull(this.options.emailVariableView)
                ) {
                    const tinymceInstance = component.view.tinymceInstance;
                    if (!tinymceInstance) {
                        return;
                    }
                    $(tinymceInstance.getBody())
                        .off(`blur${component.view.eventNamespace()}`)
                        .on(`blur${component.view.eventNamespace()}`, e => {
                            $(tinymceInstance.targetElm).trigger(e);
                        });
                }
            });
        }
    },

    _onTypeChange: function(e) {
        if (this.options.hasWysiwyg) {
            const target = $(e.target);
            if (!target.is(':checked')) {
                return;
            }

            if (target.val() === 'txt') {
                this._switchWysiwygEditor(false);
            }
            if (target.val() === 'html') {
                this._switchWysiwygEditor(true);
            }
        }
    },

    _switchWysiwygEditor: function(enabled) {
        this.options.isWysiwygEnabled = enabled;
        this.forEachComponent(function(component) {
            const view = component.view;

            if (!_.isUndefined(view) && !_.isUndefined(view.tinymceConnected)) {
                view.setIsHtml(enabled);
                this.listenToOnce(view, 'TinyMCE:initialized', this._onEditorBlur.bind(this));
            }
        });
    },

    _onEntityNameChange: function() {
        this.reloadAttachmentChoices();
    },

    reloadAttachmentChoices: function() {
        const entityName = this.$entityNameEl.val();
        if (!entityName) {
            this.attachmentChoices = [];
            this.trigger('attachments:choices:loaded', {choices: this.attachmentChoices});

            return;
        }

        mediator.execute('showLoading');
        $.post(
            routing.generate(this.options.getAttachmentsRoute, {entityName: entityName})
        ).done(response => {
            if (this.disposed) {
                return;
            }

            if (!response.successful) {
                return;
            }

            this.attachmentChoices = response.choices || [];

            this.trigger('attachments:choices:loaded', {choices: this.attachmentChoices});
        }).always(() => {
            mediator.execute('hideLoading');
        });
    },

    /**
     * @returns {Array<{value: string, label: string}>|null} List of available attachments choices or null
     *  if choices are not loaded.
     */
    getAttachmentChoices() {
        return this.attachmentChoices;
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        delete this.attachmentChoices;

        EmailTemplateEditorView.__super__.dispose.call(this);
    }
});

export default EmailTemplateEditorView;
