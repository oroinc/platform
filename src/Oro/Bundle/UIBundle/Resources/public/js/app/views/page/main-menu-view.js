/*global define*/
define([
    './../base/page/region-view'
], function (PageRegionView) {
    'use strict';

    var PageMainMenuView = PageRegionView.extend({
        template: '<%= mainMenu %>',
        pageItems: ['mainMenu']
    });

    return PageMainMenuView;
});
