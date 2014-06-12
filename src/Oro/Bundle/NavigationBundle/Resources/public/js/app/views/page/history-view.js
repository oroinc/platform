/*global define*/
define([
    'oroui/js/app/views/base/page/region-view'
], function (PageRegionView) {
    'use strict';

    var PageHistoryView = PageRegionView.extend({
        template: '<%= history %>',
        pageItems: ['history']
    });
    // @TODO check if page is not loaded from cache before render

    return PageHistoryView;
});
