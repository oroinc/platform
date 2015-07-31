define(function(require) {
    'use strict';

    var EmailTemplateCollection;
    var routing = require('routing');
    var EmailTemplateModel = require('./email-template-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroemail/js/app/models/email-template-collection
     */
    EmailTemplateCollection = BaseCollection.extend({
        route: null,
        routeId: null,
        includeNonEntity: false,
        includeSystemTemplates: true,
        url: null,
        model: EmailTemplateModel,

        /**
         * Constructor
         *
         * @param route {String}
         * @param routeId {String}
         * @param includeNonEntity {bool}
         * @param includeSystemTemplates {bool}
         */
        initialize: function(route, routeId, includeNonEntity, includeSystemTemplates) {
            this.route = route;
            this.routeId = routeId;
            this.includeNonEntity = includeNonEntity;
            this.includeSystemTemplates = includeSystemTemplates;
            var routeParams = {};
            routeParams[routeId] = null;
            this.url = routing.generate(this.route, routeParams);
        },

        /**
         * Regenerate route for selected entity
         *
         * @param id {String}
         */
        setEntityId: function(id) {
            var routeParams = {};
            routeParams[this.routeId] = id;
            routeParams.includeNonEntity = this.includeNonEntity ? '1' : '0';
            routeParams.includeSystemTemplates = this.includeSystemTemplates ? '1' : '0';
            this.url = routing.generate(this.route, routeParams);
        }
    });

    return EmailTemplateCollection;
});
