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
            // update the view if data is not from cache
            if (this.actionArgs && this.actionArgs.options.fromCache !== true) {
                HistoryView.__super__.render.call(this);
            } else {
                this._resolveDeferredRender();
            }

            return this;
        }
    });

    return HistoryView;
});
