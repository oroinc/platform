define([
    'underscore',
    'oroui/js/app/components/base/component',
    'oroorganization/js/app/views/organization-structure-tree-view'
], function(_, BaseComponent, OrganizationStructureTreeView) {
    'use strict';

    return BaseComponent.extend({
        organizationStructureTreeView: null,

        initialize: function(options) {
            if (!_.has(options, 'dataInputSelector')) {
                throw new Error('Required option "dataInputSelector" not found.');
            }

            this.organizationStructureTreeView = new OrganizationStructureTreeView({
                el: options._sourceElement,
                dataInputSelector: options.dataInputSelector
            });
        }
    });
});
