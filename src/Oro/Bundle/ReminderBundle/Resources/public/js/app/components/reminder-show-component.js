define(function(require) {
    'use strict';

    var ReminderShowComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var reminderHandler = require('ororeminder/js/reminder-handler');

    ReminderShowComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat(['reminderData']),

        initialize: function() {
            reminderHandler.setReminders(this.reminderData);
            reminderHandler.showReminders();

            ReminderShowComponent.__super__.initialize.apply(this, arguments);
        }
    });

    return ReminderShowComponent;
});
