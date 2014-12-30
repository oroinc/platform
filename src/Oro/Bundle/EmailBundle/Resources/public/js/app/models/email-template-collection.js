/*global define*/
define(function (require) {
    'use strict';

    var EmailTemplateCollection,
        routing = require('routing'),
        EmailTemplateModel = require('./email-template-model'),
        BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroemail/js/app/models/email-template-collection
     */
    EmailTemplateCollection = BaseCollection.extend({
        route: null,
        routeId: null,
        url: null,
        model: EmailTemplateModel,

        /**
         * Constructor
         *
         * @param route {String}
         * @param routeId {String}
         */
        initialize: function (route, routeId) {
            this.route = route;
            this.routeId = routeId;
            var routeParams = {};
            routeParams[routeId] = null;
            this.url = routing.generate(this.route, routeParams);
        },

        /**
         * Regenerate route for selected entity
         *
         * @param id {String}
         */
        setEntityId: function (id) {
            var routeParams = {};
            routeParams[this.routeId] = id;
            this.url = routing.generate(this.route, routeParams);
        }
    });

    return EmailTemplateCollection;
});
