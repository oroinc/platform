define([
    './../base/page-region-view'
], function(PageRegionView) {
    'use strict';

    const PageUserMenuView = PageRegionView.extend({
        /**
         * @inheritdoc
         */
        constructor: function PageUserMenuView(options) {
            PageUserMenuView.__super__.constructor.call(this, options);
        },

        template: function(data) {
            return data.userMenu;
        },
        pageItems: ['userMenu']
    });

    return PageUserMenuView;
});
