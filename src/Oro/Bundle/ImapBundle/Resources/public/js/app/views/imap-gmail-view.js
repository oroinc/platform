import BaseView from 'oroimap/js/app/views/imap-view';
const ImapGmailView = BaseView.extend({
    /**
     * @inheritdoc
     */
    constructor: function ImapGmailView(options) {
        ImapGmailView.__super__.constructor.call(this, options);
    }
});

export default ImapGmailView;
