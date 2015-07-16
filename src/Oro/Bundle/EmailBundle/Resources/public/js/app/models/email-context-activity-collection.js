define(function(require) {
    'use strict';

    var EmailContextActivityCollection;
    var EmailContextActivityModel = require('./email-context-activity-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroemail/js/app/models/email-context-collection
     */
    EmailContextActivityCollection = BaseCollection.extend({
        route: null,
        routeId: null,
        includeNonEntity: false,
        includeSystemTemplates: true,
        url: null,
        model: EmailContextActivityModel
    });

    return EmailContextActivityCollection;
});
