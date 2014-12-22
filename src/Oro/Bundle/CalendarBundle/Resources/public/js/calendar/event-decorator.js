/*jslint nomen:true*/
/*global define*/
define(['underscore', 'orotranslation/js/translator'
    ], function (_, __) {
    'use strict';

    return {
        /** @property {Object} */
        templates: {
            reminderIcon: '<i class="reminder-status icon-bell" title="' + __('Reminders') + '"></i>',
            notRespondedIcon: '<i class="invitation-status icon-reply" title="' + __('Not responded') + '"></i>',
            tentativelyIcon: '<i class="invitation-status icon-question-sign" title="' + __('Tentatively accepted') + '"></i>',
            acceptedIcon: '<i class="invitation-status icon-ok" title="' + __('Accepted') + '"></i>'
        },

        decorate: function (eventModel, $el) {
            var $body = $el.find('.fc-content'),
                $timePlace = $el.find('.fc-time'),
                reminders = eventModel.get('reminders'),
                invitationStatus = eventModel.getInvitationStatus();
            // if $time is not displayed show related info into $body
            if (!$timePlace.length) {
                $timePlace = $body;
            }
            if (reminders && _.keys(reminders).length) {
                $el.prepend(this.templates.reminderIcon);
            } else {
                $el.find('.reminder-status').remove();
            }
            switch (invitationStatus) {
                case 'not_responded':
                    $timePlace.prepend(this.templates.notRespondedIcon);
                    break;
                case 'accepted':
                    $timePlace.prepend(this.templates.acceptedIcon);
                    break;
                case 'tentatively_accepted':
                    $timePlace.prepend(this.templates.tentativelyIcon);
                    break;
                case 'declined':
                    $body.addClass('invitation-status-declined');
                    break;
                default:
                    $body.find('.invitation-status').remove();
                    $body.removeClass('invitation-status-declined');
                    eventModel._isInvitationIconAdded = false;
                    break;
            }
        }
    };
});
