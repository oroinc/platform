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
        includeSystem: false,
        url: null,
        model: EmailTemplateModel,

        /**
         * Constructor
         *
         * @param route {String}
         * @param routeId {String}
         * @param includeSystem {bool}
         */
        initialize: function (route, routeId, includeSystem) {
            console.log(includeSystem);
            this.route = route;
            this.routeId = routeId;
            this.includeSystem = includeSystem;
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
            routeParams['includeSystem'] = this.includeSystem ? '1' : '0';
            this.url = routing.generate(this.route, routeParams);
        }
    });

    return EmailTemplateCollection;
});
