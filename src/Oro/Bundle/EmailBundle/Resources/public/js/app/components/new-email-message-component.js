import _ from 'underscore';
import sync from 'orosync/js/sync';
import BaseComponent from 'oroui/js/app/components/base/component';
import moduleConfig from 'module-config';
const config = moduleConfig(module.id);

const NewEmailMessageComponent = BaseComponent.extend({
    onNewEmailDebounced: null,

    /**
     * @inheritdoc
     */
    constructor: function NewEmailMessageComponent(options) {
        NewEmailMessageComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        const channel = config.wsChannel;
        this.onNewEmailDebounced = _.debounce(this.onNewEmail.bind(this, options._sourceElement), 6000, true);
        sync.subscribe(channel, this.onMessage.bind(this));
    },

    /**
     * @param {Array} message
     */
    onMessage: function(message) {
        if (_.result(message, 'hasNewEmail') === true) {
            this.onNewEmailDebounced();
        }
    },

    onNewEmail: function($notificationElement) {
        if ($notificationElement.parent().hasClass('show') === false) {
            $notificationElement.show().delay(5000).fadeOut(1000);
        }
    }
});

export default NewEmailMessageComponent;
