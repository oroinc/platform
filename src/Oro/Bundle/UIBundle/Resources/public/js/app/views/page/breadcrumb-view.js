/*global define*/
define([
    './../base/page/region-view'
], function (PageRegionView) {
    'use strict';

    var PageBreadcrumbView = PageRegionView.extend({
        template: '<%= breadcrumb %>',
        pageItems: ['breadcrumb']
    });

    return PageBreadcrumbView;
});
