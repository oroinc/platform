/*global define*/
define([
    'oroui/js/app/views/base/page/region-view'
], function (PageRegionView) {
    'use strict';

    var PageHistoryView = PageRegionView.extend({
        template: '<%= history %>',
        pageItems: ['history'],

        render: function () {
            // does not update view is data is from cache
            if (!this.actionArgs || this.actionArgs.options.fromCache === true) {
                return;
            }

            PageRegionView.prototype.render.call(this);
        }
    });

    return PageHistoryView;
});
