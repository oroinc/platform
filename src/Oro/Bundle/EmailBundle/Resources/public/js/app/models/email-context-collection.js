/*global define*/
define(function (require) {
    'use strict';

    var EmailContextCollection,
        EmailContextModel = require('./email-context-model'),
        BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroemail/js/app/models/email-context-collection
     */
    EmailContextCollection = BaseCollection.extend({
        route: null,
        routeId: null,
        includeSystem: false,
        url: null,
        model: EmailContextModel
    });

    return EmailContextCollection;
});
