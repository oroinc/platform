import PageRegionView from 'oroui/js/app/views/base/page-region-view';

const OrganizationSwitchView = PageRegionView.extend({
    template: function(data) {
        return data.organization_switch;
    },

    /**
     * @inheritdoc
     */
    constructor: function OrganizationSwitchView(options) {
        OrganizationSwitchView.__super__.constructor.call(this, options);
    }
});

export default OrganizationSwitchView;
