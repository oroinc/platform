/*jslint nomen:true*/
/*global define*/
define([
    'oroui/js/app/views/base/page-region-view'
], function (PageRegionView) {
    'use strict';

    var PageHistoryView;

    PageHistoryView = PageRegionView.extend({
        template: function (data) {
            return data.history;
        },
        pageItems: ['history'],

        render: function () {
            // does not update view is data is from cache
            if (!this.actionArgs || this.actionArgs.options.fromCache === true) {
                return;
            }

            PageHistoryView.__super__.render.call(this);
        }
    });

    return PageHistoryView;
});
