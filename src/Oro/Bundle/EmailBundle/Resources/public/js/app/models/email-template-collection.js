define(function(require) {
    'use strict';

    const routing = require('routing');
    const EmailTemplateModel = require('./email-template-model');
    const BaseCollection = require('oroui/js/app/models/base/collection');
    const _ = require('underscore');
    const systemAccessModeOrganizationProvider =
        require('oroorganization/js/app/tools/system-access-mode-organization-provider').default;
    /**
     * @export oroemail/js/app/models/email-template-collection
     */
    const EmailTemplateCollection = BaseCollection.extend({
        route: null,

        routeId: null,

        includeNonEntity: false,

        includeSystemTemplates: true,

        _sa_org_id: null,

        url: null,

        model: EmailTemplateModel,

        /**
         * @inheritdoc
         */
        constructor: function EmailTemplateCollection(...args) {
            EmailTemplateCollection.__super__.constructor.apply(this, args);
        },

        /**
         * @inheritdoc
         */
        initialize: function(models, options) {
            _.extend(this, _.pick(options, [
                'route',
                'routeId',
                'includeNonEntity',
                'includeSystemTemplates',
                '_sa_org_id'
            ]));

            const routeParams = {};

            routeParams[this.routeId] = null;

            this.url = routing.generate(this.route, routeParams);

            EmailTemplateCollection.__super__.initialize.call(this, models, options);
        },

        /**
         * Regenerate route for selected entity
         *
         * @param {String} id
         */
        setEntityId: function(id) {
            const routeParams = {};
            routeParams[this.routeId] = id;
            routeParams.includeNonEntity = this.includeNonEntity ? '1' : '0';
            routeParams.includeSystemTemplates = this.includeSystemTemplates ? '1' : '0';
            routeParams._sa_org_id = systemAccessModeOrganizationProvider.getOrganizationId();
            this.url = routing.generate(this.route, routeParams);
        }
    });

    return EmailTemplateCollection;
});
