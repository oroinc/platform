define([
    'oroui/js/app/views/base/page-region-view'
], function(PageRegionView) {
    'use strict';

    var OrganizationSwitchView;

    OrganizationSwitchView = PageRegionView.extend({
        template: function(data) {
            return data.organization_switch;
        },

        /**
         * @inheritDoc
         */
        constructor: function OrganizationSwitchView() {
            OrganizationSwitchView.__super__.constructor.apply(this, arguments);
        },

        pageItems: ['organization_switch']
    });

    return OrganizationSwitchView;
});
