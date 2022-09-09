import $ from 'jquery';
import sync from 'orosync/js/sync';
import messenger from 'oroui/js/messenger';
import mediator from 'oroui/js/mediator';
import routing from 'routing';
import {macros} from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseComponent from 'oroui/js/app/components/base/component';

const ReminderHandlerComponent = BaseComponent.extend({
    removeDates: null,

    reminders: null,

    listen: {
        'page:afterChange mediator': 'onPageAfterChange'
    },

    /**
     * @inheritdoc
     */
    constructor: function ReminderHandlerComponent(options) {
        ReminderHandlerComponent.__super__.constructor.call(this, options);
    },

    /**
     * Initialize event listening
     *
     * @param {Object} options
     * @param {number} options.userId Current user id
     * @param {boolean} options.wampEnable Is WAMP enabled
     */
    initialize({userId, wampEnable}) {
        this.removeDates = {};
        this.reminders = {};

        if (wampEnable) {
            this.initWamp(userId);
        }

        mediator.setHandler('reminder:publish', this.publish.bind(this));
    },

    /**
     * Initialize WAMP subscribing
     *
     * @param {number} id Current user id
     */
    initWamp(id) {
        sync.subscribe('oro/reminder_remind/' + id, this.publish.bind(this));
    },

    onPageAfterChange() {
        // to make reminders publication after emptying messages container by PageMessagesView on `page:afterChange`
        setTimeout(this.showReminders.bind(this));
    },

    /**
     * Sets new list of reminders and shows them
     *
     * @param {Array.<Object>} reminders
     */
    publish(reminders) {
        this.addReminders(reminders);
        this.showReminders();
    },

    /**
     * Set reminders
     *
     * @param {Array.<Object>}  reminders
     */
    setReminders(reminders) {
        this.reminders = {};
        this.addReminders(reminders);
    },

    /**
     * Add reminders
     *
     * @param {Array.<Object>} reminders
     */
    addReminders(reminders) {
        reminders.forEach(reminder => this.addReminder(reminder));
    },

    /**
     * Add reminder
     *
     * @param {Object} newReminder
     */
    addReminder(newReminder) {
        const {id: newId, uniqueId} = newReminder;
        const oldReminder = this.reminders[uniqueId];
        if (!oldReminder) {
            const removeDate = this.removeDates[uniqueId];
            const currentDate = new Date();
            // If was already removed less then 60 secs ago, ignore it
            if (removeDate && currentDate.getTime() - removeDate.getTime() < 60000) {
                return;
            }
            this.reminders[uniqueId] = newReminder;
        } else if (oldReminder.id !== newId) {
            oldReminder.duplicateIds = oldReminder.duplicateIds || [];
            if (oldReminder.duplicateIds.indexOf(newId) === -1) {
                oldReminder.duplicateIds.push(newId);
            }
        }
    },

    /**
     * Remove reminder by uinque id
     *
     * @param {string} uniqueId
     */
    removeReminder(uniqueId) {
        const reminder = this.reminders[uniqueId];
        if (!reminder) {
            return;
        }
        const url = routing.generate('oro_api_post_reminder_shown');
        const removeIds = reminder.duplicateIds || [];
        removeIds.push(reminder.id);

        $.post(url, {ids: removeIds});

        this.removeDates[uniqueId] = new Date();

        delete this.reminders[uniqueId];
    },

    /**
     * Show reminders
     */
    showReminders() {
        Object.values(this.reminders).forEach(reminder => {
            const {uniqueId} = reminder;
            let message = this.getReminderMessage(reminder);
            message += ` (<a data-dismiss="alert" href="#">${__('oro.reminder.dismiss.label')}</a>)`;

            messenger.notificationMessage('reminder', message, {
                onClose: this.removeReminder.bind(this, uniqueId)
            });
        });
    },

    /**
     * @param {Object} reminder
     * @returns {string}
     */
    getReminderMessage(reminder) {
        const templates = macros('reminderTemplates');
        const template = templates[reminder.templateId] || templates['default'];
        return template(reminder);
    }
});

export default ReminderHandlerComponent;
