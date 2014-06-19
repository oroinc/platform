/*global define*/
define([
    './../base/page-region-view'
], function (PageRegionView) {
    'use strict';

    var PageMainMenuView;

    PageMainMenuView = PageRegionView.extend({
        template: function (data) {
            return data.mainMenu;
        },
        pageItems: ['mainMenu']
    });

    return PageMainMenuView;
});
