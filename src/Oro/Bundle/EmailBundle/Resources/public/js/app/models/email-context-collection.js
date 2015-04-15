/*global define*/
define(function (require) {
    'use strict';

    var EmailContextCollection,
        EmailContextModel = require('./email-context-model'),
        BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroemail/js/app/models/email-template-collection
     */
    EmailContextCollection = BaseCollection.extend({
        model: EmailContextModel
    });

    return EmailContextCollection;
});