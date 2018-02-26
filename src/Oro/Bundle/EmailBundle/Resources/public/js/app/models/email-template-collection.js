define(function(require) {
    'use strict';

    var EmailTemplateCollection;
    var routing = require('routing');
    var EmailTemplateModel = require('./email-template-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var _ = require('underscore');

    /**
     * @export oroemail/js/app/models/email-template-collection
     */
    EmailTemplateCollection = BaseCollection.extend({
        route: null,

        routeId: null,

        includeNonEntity: false,

        includeSystemTemplates: true,

        url: null,

        model: EmailTemplateModel,

        /**
         * @inheritDoc
         */
        constructor: function EmailTemplateCollection() {
            EmailTemplateCollection.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(models, options) {
            _.extend(this, _.pick(options, ['route', 'routeId', 'includeNonEntity', 'includeSystemTemplates']));

            var routeParams = {};

            routeParams[this.routeId] = null;

            this.url = routing.generate(this.route, routeParams);

            EmailTemplateCollection.__super__.initialize.apply(this, arguments);
        },

        /**
         * Regenerate route for selected entity
         *
         * @param {String} id
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
