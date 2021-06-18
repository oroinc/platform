define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const reminderHandler = require('ororeminder/js/reminder-handler');

    const ReminderShowComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat(['reminderData']),

        /**
         * @inheritdoc
         */
        constructor: function ReminderShowComponent(options) {
            ReminderShowComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            reminderHandler.setReminders(this.reminderData);
            reminderHandler.showReminders();

            ReminderShowComponent.__super__.initialize.call(this, options);
        }
    });

    return ReminderShowComponent;
});
