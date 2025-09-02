import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';

const EmailTemplateAttachmentView = BaseView.extend({
    /**
     * @inheritDoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'emailTemplateEditorView'
    ]),

    /**
     * @inheritDoc
     */
    events: {
        'change :input[data-name="field__file-placeholder"]': '_onFilePlaceholderChange'
    },

    /**
     * @property {jQuery.Element|null}
     */
    $filePlaceholderEl: null,

    /**
     * @property {jQuery.Element|null}
     */
    $filePlaceholderContainer: null,

    /**
     * @property {jQuery.Element|null}
     */
    $fileContainer: null,

    constructor: function EmailTemplateAttachmentView(options) {
        EmailTemplateAttachmentView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        EmailTemplateAttachmentView.__super__.initialize.call(this, options);

        this.$filePlaceholderContainer = this.$el.find('[data-role="email-template-attachment-file-placeholder"]');
        this.$fileContainer = this.$el.find('[data-role="email-template-attachment-file"]');
        this.$filePlaceholderEl = this.$el.find(':input[data-name="field__file-placeholder"]');

        this.listenTo(this.emailTemplateEditorView, 'attachments:choices:loaded',
            this._onAttachmentChoicesLoaded.bind(this));
        this.updateAttachmentChoices(this.emailTemplateEditorView.getAttachmentChoices() || null);
    },

    /**
     * @param {jQuery.Event} event
     *
     * @private
     */
    _onFilePlaceholderChange(event) {
        if (this.$filePlaceholderEl.val() === '__upload_file__') {
            this.$fileContainer.removeClass('hide');
        } else {
            this.$fileContainer.addClass('hide');
        }
    },

    /**
     * Update the attachments choices in the Attachment field with the email template attachments variables.
     *
     * @param {Object} data
     *  {
     *      choices: Array<{value: string, label: string}>,
     *  }
     *
     * @private
     */
    _onAttachmentChoicesLoaded(data) {
        this.updateAttachmentChoices(data.choices || []);
    },

    /**
     * @param {Array<{value: string, label: string}>} choices
     */
    updateAttachmentChoices(choices) {
        const $originalOptions = this.$filePlaceholderEl.find('> option[value!=""]:not([value="__upload_file__"])');

        if (choices === null) {
            choices = $originalOptions.map((index, option) => {
                const $option = $(option);

                return {
                    value: $option.val(),
                    label: $option.text()
                };
            }).get();
        } else if (choices.length) {
            const originalValue = this.$filePlaceholderEl.val();
            let originalValueIsPresent = originalValue === '__upload_file__';

            $originalOptions.remove();

            this.$filePlaceholderEl.prepend(
                choices.map(attachment => {
                    if (originalValue === attachment.value) {
                        originalValueIsPresent = true;
                    }

                    return $('<option>', {
                        value: attachment.value,
                        text: attachment.label
                    });
                })
            );

            this.$filePlaceholderEl.val(originalValueIsPresent ? originalValue : null).trigger('change');
        }

        if (choices.length) {
            this.$filePlaceholderContainer.removeClass('hide');
        } else {
            this.$filePlaceholderEl.val('__upload_file__').trigger('change');
            this.$filePlaceholderContainer.addClass('hide');
        }

        this.$filePlaceholderEl.inputWidget('refresh');
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        delete this.emailTemplateEditorView;

        EmailTemplateAttachmentView.__super__.dispose.call(this);
    }
});

export default EmailTemplateAttachmentView;
