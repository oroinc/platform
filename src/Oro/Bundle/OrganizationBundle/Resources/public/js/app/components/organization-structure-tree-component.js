define([
    'underscore',
    'oroui/js/app/components/base/component',
    'oroorganization/js/app/views/organization-structure-tree-view'
], function(_, BaseComponent, OrganizationStructureTreeView) {
    'use strict';

    var OrganizationStructureTreeComponent;

    OrganizationStructureTreeComponent = BaseComponent.extend({
        organizationStructureTreeView: null,

        requiredOptions: [
            'dataInputSelector',
            'tree',
            'selectedBusinessUnits',
            'selectedOrganizations',
            'accordionEnabled'
        ],

        /**
         * @inheritDoc
         */
        constructor: function OrganizationStructureTreeComponent() {
            OrganizationStructureTreeComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.each(this.requiredOptions, function(optionName) {
                if (!_.has(options, optionName)) {
                    throw new Error('Required option "' + optionName + '" not found.');
                }
            });

            this.organizationStructureTreeView = new OrganizationStructureTreeView({
                el: options._sourceElement,
                dataInputSelector: options.dataInputSelector,
                tree: options.tree,
                selectedBusinessUnits: options.selectedBusinessUnits,
                selectedOrganizations: options.selectedOrganizations,
                accordionEnabled: options.accordionEnabled
            });
        }
    });

    return OrganizationStructureTreeComponent;
});
