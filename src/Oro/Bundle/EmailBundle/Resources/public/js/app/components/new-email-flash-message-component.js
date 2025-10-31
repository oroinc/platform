import _ from 'underscore';
import routing from 'routing';
import messenger from 'oroui/js/messenger';
import NewEmailMessageComponent from 'oroemail/js/app/components/new-email-message-component';

const NewEmailFlashMessageComponent = NewEmailMessageComponent.extend({
    messageTpl: _.template('<%- _.__("oro.email.notification.new_email")%>' +
        '<span class="separator">|</span><a href="<%-url %>"><%- _.__("Read") %></a>'),

    /**
     * @inheritdoc
     */
    constructor: function NewEmailFlashMessageComponent(options) {
        NewEmailFlashMessageComponent.__super__.constructor.call(this, options);
    },

    onNewEmail: function() {
        const message = this.messageTpl({url: routing.generate('oro_email_user_emails')});
        messenger.notificationFlashMessage('info', message);
    }
});

export default NewEmailFlashMessageComponent;
