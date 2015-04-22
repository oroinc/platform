/*global define*/
define(function (require) {
    'use strict';

    var EmailContextActivityCollection,
        EmailContextActivityModel = require('./email-context-activity-model'),
        BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroemail/js/app/models/email-context-collection
     */
    EmailContextActivityCollection = BaseCollection.extend({
        route: null,
        routeId: null,
        includeNonEntity: false,
        url: null,
        model: EmailContextActivityModel
    });

    return EmailContextActivityCollection;
});
