/*jslint nomen:true*/
/*global define*/
define([
    'oroui/js/app/views/base/page-region-view'
], function (PageRegionView) {
    'use strict';

    var HistoryView;

    HistoryView = PageRegionView.extend({
        template: function (data) {
            return data.history;
        },
        pageItems: ['history'],

        render: function () {
            // does not update view if data is from cache
            if (!this.actionArgs || this.actionArgs.options.fromCache === true) {
                return this;
            }

            return HistoryView.__super__.render.call(this);
        }
    });

    return HistoryView;
});
