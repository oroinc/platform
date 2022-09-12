import {macros} from 'underscore';

macros('reminderTemplates', {
    /**
     * Renders contend for a default reminder massage;
     *
     * @param {Object} data
     * @param {string} data.subject
     * @param {string} data.expireAt
     * @param {string?} data.url
     */
    'default': require('tpl-loader!ororeminder/templates/macros/default-reminder-template.html')
});
