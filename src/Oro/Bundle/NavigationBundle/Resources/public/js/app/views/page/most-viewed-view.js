/*jslint nomen:true*/
/*global define*/
define([
    'oroui/js/app/views/base/page-region-view'
], function (PageRegionView) {
    'use strict';

    var PageMostViewedView;

    PageMostViewedView = PageRegionView.extend({
        template: function (data) {
            return data.mostviewed;
        },
        pageItems: ['mostviewed'],

        render: function () {
            // does not update view is data is from cache
            if (!this.actionArgs || this.actionArgs.options.fromCache === true) {
                return;
            }

            PageMostViewedView.__super__.render.call(this);
        }
    });

    return PageMostViewedView;
});
