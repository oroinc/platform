define([
    './../base/page-region-view'
], function(PageRegionView) {
    'use strict';

    var PageUserMenuView;

    PageUserMenuView = PageRegionView.extend({
        /**
         * @inheritDoc
         */
        constructor: function PageUserMenuView() {
            PageUserMenuView.__super__.constructor.apply(this, arguments);
        },

        template: function(data) {
            return data.userMenu;
        },
        pageItems: ['userMenu']
    });

    return PageUserMenuView;
});
