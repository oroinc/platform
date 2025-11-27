import PageRegionView from './../base/page-region-view';

const PageUserMenuView = PageRegionView.extend({
    template: function(data) {
        return data.usermenu;
    },

    pageItems: ['usermenu'],

    /**
     * @inheritdoc
     */
    constructor: function PageUserMenuView(options) {
        PageUserMenuView.__super__.constructor.call(this, options);
    }
});

export default PageUserMenuView;
