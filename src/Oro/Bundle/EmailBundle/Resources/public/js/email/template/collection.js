/*global define*/
define(['backbone', 'routing', './model'
    ], function (Backbone, routing, EmailTemplateModel) {
    'use strict';

    /**
     * @export  oroemail/js/email/template/collection
     * @class   oroemail.email.template.Collection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
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
});
