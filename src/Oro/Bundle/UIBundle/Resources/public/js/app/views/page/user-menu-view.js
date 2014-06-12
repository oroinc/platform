/*global define*/
define([
    './../base/page/region-view'
], function (PageRegionView) {
    'use strict';

    var PageUserMenuView = PageRegionView.extend({
        template: '<%= userMenu %>',
        pageItems: ['userMenu']
    });

    return PageUserMenuView;
});
