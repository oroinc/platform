define(function(require) {
    'use strict';

    var ReminderShowComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var reminderHandler = require('ororeminder/js/reminder-handler');

    ReminderShowComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat(['reminderData']),

        /**
         * @inheritDoc
         */
        constructor: function ReminderShowComponent() {
            ReminderShowComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            reminderHandler.setReminders(this.reminderData);
            reminderHandler.showReminders();

            ReminderShowComponent.__super__.initialize.apply(this, arguments);
        }
    });

    return ReminderShowComponent;
});
