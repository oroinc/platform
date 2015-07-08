define(function(require) {
    'use strict';

    var EmailContextCollection;
    var EmailContextModel = require('./email-context-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroemail/js/app/models/email-template-collection
     */
    EmailContextCollection = BaseCollection.extend({
        model: EmailContextModel
    });

    return EmailContextCollection;
});
