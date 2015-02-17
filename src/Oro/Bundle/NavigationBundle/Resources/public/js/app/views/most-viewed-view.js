/*jslint nomen:true*/
/*global define*/
define([
    'oroui/js/app/views/base/page-region-view'
], function (PageRegionView) {
    'use strict';

    var MostViewedView;

    MostViewedView = PageRegionView.extend({
        template: function (data) {
            return data.mostviewed;
        },
        pageItems: ['mostviewed'],

        render: function () {
            // update the view if data is not from cache
            if (this.actionArgs && this.actionArgs.options.fromCache !== true) {
                MostViewedView.__super__.render.call(this);
            } else {
                this._resolveDeferredRender();
            }

            return this;
        }
    });

    return MostViewedView;
});
