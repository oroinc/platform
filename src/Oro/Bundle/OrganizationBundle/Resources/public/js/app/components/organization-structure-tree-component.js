define([
    'oroui/js/app/components/base/component',
    'oroorganization/js/app/views/organization-structure-tree-view'
], function(BaseComponent, OrganizationStructureTreeView) {
    'use strict';

    return BaseComponent.extend({
        organizationStructureTreeView: null,

        initialize: function(options) {
            this.organizationStructureTreeView = new OrganizationStructureTreeView({
                el: options._sourceElement
            });
        }
    });
});
