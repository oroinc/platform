import routing from 'routing';
import EmailTemplateModel from './email-template-model';
import BaseCollection from 'oroui/js/app/models/base/collection';
import _ from 'underscore';
import systemAccessModeOrganizationProvider
    from 'oroorganization/js/app/tools/system-access-mode-organization-provider';
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

export default EmailTemplateCollection;
