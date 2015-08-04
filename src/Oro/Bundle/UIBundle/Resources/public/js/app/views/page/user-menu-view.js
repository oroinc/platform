define([
    './../base/page-region-view'
], function(PageRegionView) {
    'use strict';

    var PageUserMenuView;

    PageUserMenuView = PageRegionView.extend({
        template: function(data) {
            return data.userMenu;
        },
        pageItems: ['userMenu']
    });

    return PageUserMenuView;
});
