/*global define*/
define([
    'oroui/js/app/views/base/page/region-view'
], function (PageRegionView) {
    'use strict';

    var PageMostViewedView = PageRegionView.extend({
        template: '<%= mostviewed %>',
        pageItems: ['mostviewed']
    });
    // @TODO check if page is not loaded from cache before render

    return PageMostViewedView;
});
