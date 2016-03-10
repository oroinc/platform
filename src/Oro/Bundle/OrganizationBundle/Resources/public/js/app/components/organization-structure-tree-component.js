define([
    'underscore',
    'oroui/js/app/components/base/component',
    'oroorganization/js/app/views/organization-structure-tree-view'
], function(_, BaseComponent, OrganizationStructureTreeView) {
    'use strict';

    return BaseComponent.extend({
        organizationStructureTreeView: null,

        requiredOptions: [
            'dataInputSelector',
            'tree',
            'selectedBusinessUnits',
            'selectedOrganizations',
            'accordionEnabled'
        ],

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
});
