define(function(require) {
    'use strict';

    var ReminderShowView;
    var BaseView = require('oroui/js/app/views/base/view');
    var reminderHandler = require('ororeminder/js/reminder-handler');

    ReminderShowView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['reminderData']),

        initialize: function() {
            reminderHandler.setReminders(this.reminderData);
            reminderHandler.showReminders();

            ReminderShowView.__super__.initialize.apply(this, arguments);
        }
    });

    return ReminderShowView;
});
