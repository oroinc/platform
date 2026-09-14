import BaseView from 'oroui/js/app/views/base/view';
import $ from 'jquery';

const ImapView = BaseView.extend({
    events: {
        'click button[name$="[userEmailOrigin][check]"]': 'onClickConnect',
        'click button[name$="[userEmailOrigin][checkFolder]"]': 'onCheckFolder',
        'click button.delete': 'onResetEmail'
    },

    $vendorErrorMessage: null,

    errorMessage: '',

    type: '',

    html: '',

    oauthTokenHandle: '',

    email: '',

    /**
     * @inheritdoc
     */
    constructor: function ImapView(options) {
        ImapView.__super__.constructor.call(this, options);
    },

    /**
     * @constructor
     *
     * @param {Object} options
     */
    initialize: function(options) {
        this.vendorErrorMessage = options.vendorErrorMessage;
        this.type = options.type;
    },

    render: function() {
        if (this.html && this.html.length > 0) {
            this.$el.html(this.html);
        }

        this.$el.find('input[name$="[userEmailOrigin][oauthTokenHandle]"]').val(this.oauthTokenHandle);
        this.$el.find('input[name$="[userEmailOrigin][user]"]').val(this.email);

        if (this.errorMessage.length > 0) {
            this.showErrorMessage();
        } else {
            this.hideErrorMessage();
        }

        this.initLayout().then(this._resolveDeferredRender.bind(this));
    },

    /**
     * Forces separate layout initialization for imap views
     *
     * @inheritdoc
     */
    hasOwnLayout: function() {
        return true;
    },

    /**
     * Set error message
     * @param {string} message
     */
    setErrorMessage: function(message) {
        this.errorMessage = message;
    },

    /**
     * Clear error message
     */
    resetErrorMessage: function() {
        this.errorMessage = '';
    },

    /**
     * Set html template
     * @param {string} html
     */
    setHtml: function(html) {
        this.html = html;
    },

    /**
     * Handler event of click on the button Connection
     */
    onClickConnect: function() {
        this.trigger('checkConnection', this.getData());
    },

    /**
     * Handler event of click on the button Retrieve Folders
     */
    onCheckFolder: function() {
        this.trigger('getFolders', this.getData());
    },

    /**
     * Handler event of click 'x' button
     */
    onResetEmail: function() {
        $('select[name$="[accountType]"]').val('').trigger('change');
    },

    /**
     * Return values from types of form
     * @returns {{type: string, oauthTokenHandle: *, clientId: *, user: *, imapPort: *, imapHost: *, imapEncryption: *, smtpPort: *, smtpHost: *, smtpEncryption: *}}
     */
    getData: function() {
        let oauthTokenHandle = this.$el.find('input[name$="[userEmailOrigin][oauthTokenHandle]"]').val();
        oauthTokenHandle = oauthTokenHandle || this.oauthTokenHandle;

        return {
            type: this.type,
            oauthTokenHandle: oauthTokenHandle,
            clientId: this.$el.find('input[name$="[userEmailOrigin][clientId]"]').val(),
            user: this.$el.find('input[name$="[userEmailOrigin][user]"]').val(),
            imapPort: this.$el.find('input[name$="[userEmailOrigin][imapPort]"]').val(),
            imapHost: this.$el.find('input[name$="[userEmailOrigin][imapHost]"]').val(),
            imapEncryption: this.$el.find('input[name$="[userEmailOrigin][imapEncryption]"]').val(),
            smtpPort: this.$el.find('input[name$="[userEmailOrigin][smtpPort]"]').val(),
            smtpHost: this.$el.find('input[name$="[userEmailOrigin][smtpHost]"]').val(),
            smtpEncryption: this.$el.find('input[name$="[userEmailOrigin][smtpEncryption]"]').val()
        };
    },

    /**
     * Set OAuth token handle
     * @param {string} value
     */
    setOAuthTokenHandle: function(value) {
        this.oauthTokenHandle = value;
    },

    /**
     * Set email
     * @param {string} value
     */
    setEmail: function(value) {
        this.email = value;
    },

    /**
     * Change style for block with error message to show
     */
    showErrorMessage: function() {
        const $errorBlock = this.getErrorBlock();

        if ($errorBlock.length > 0) {
            $errorBlock.html(this.errorMessage);
            $errorBlock.show();
        }
    },

    /**
     * Change style for block with error message to hide
     */
    hideErrorMessage: function() {
        const $errorBlock = this.getErrorBlock();
        if ($errorBlock.length > 0) {
            $errorBlock.hide();
        }
    },

    /**
     * @returns {*}
     */
    getErrorBlock: function() {
        return this.$el.find(this.vendorErrorMessage);
    },

    /**
     * Try to load folders tree if it is not loaded
     */
    autoRetrieveFolders: function() {
        if (!this.$el.find('input.folder-tree').length) {
            this.trigger('getFolders', this.getData());
        }
    }
});

export default ImapView;
