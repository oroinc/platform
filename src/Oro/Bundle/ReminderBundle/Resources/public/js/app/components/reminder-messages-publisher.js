import mediator from 'oroui/js/mediator';

export default function({reminders}) {
    mediator.execute('reminder:publish', reminders || []);
};
