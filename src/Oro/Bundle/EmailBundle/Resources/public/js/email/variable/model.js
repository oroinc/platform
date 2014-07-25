/*global define*/
define(['backbone', 'routing'
    ], function (Backbone, routing) {
    'use strict';

    /**
     * @export  oroemail/js/email/variable/model
     * @class   oroemail.email.variable.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        route:      'oro_api_get_emailtemplate_available_variables',
        url:        null,

        /**
         * Entity class name
         *
         * @property {string}
         */
        entityName: null,

        setEntityName: function (entityName) {
            this.url = routing.generate(this.route, {entityName: entityName});
        },

        fetch: function(options) {
            this.clear({silent: true});
            Backbone.Model.prototype.fetch.apply(this, arguments);
        }
    });
});
