/*global define*/
define([
    './../base/page/region-view'
], function (PageRegionView) {
    'use strict';

    var PageBreadcrumbView;

    PageBreadcrumbView = PageRegionView.extend({
        template: function (data) {
            return data.breadcrumb;
        },
        pageItems: ['breadcrumb']
    });

    return PageBreadcrumbView;
});
