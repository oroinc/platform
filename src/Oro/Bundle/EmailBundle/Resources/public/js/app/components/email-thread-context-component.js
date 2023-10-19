import BaseComponent from 'oroactivity/js/app/components/activity-context-activity-component';

const EmailThreadActivityContextComponent = BaseComponent.extend({
    /**
     * @inheritdoc
     */
    constructor: function EmailThreadActivityContextComponent(options) {
        EmailThreadActivityContextComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    getViewOptions: function() {
        const options = EmailThreadActivityContextComponent.__super__.getViewOptions.call(this);
        options.getRoute = 'oro_api_get_activity_email_thread_context';
        options.deleteRoute = 'oro_api_delete_activity_email_thread_context';
        return options;
    }
});

export default EmailThreadActivityContextComponent;
