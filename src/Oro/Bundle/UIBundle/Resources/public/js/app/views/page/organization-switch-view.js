define([
    'oroui/js/app/views/base/page-region-view'
], function(PageRegionView) {
    'use strict';

    const OrganizationSwitchView = PageRegionView.extend({
        template: function(data) {
            return data.organization_switch;
        },

        /**
         * @inheritdoc
         */
        constructor: function OrganizationSwitchView(options) {
            OrganizationSwitchView.__super__.constructor.call(this, options);
        },

        pageItems: ['organization_switch']
    });

    return OrganizationSwitchView;
});
