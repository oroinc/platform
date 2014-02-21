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
        route: 'oro_api_get_emailtemplate',
        url: null,
        model: EmailTemplateModel,

        /**
         * Constructor
         */
        initialize: function () {
            this.url = routing.generate(this.route, {entityName: null});
        },

        /**
         * Regenerate route for selected entity
         *
         * @param id {String}
         */
        setEntityId: function (id) {
            this.url = routing.generate(this.route, {entityName: id});
        }
    });
});
