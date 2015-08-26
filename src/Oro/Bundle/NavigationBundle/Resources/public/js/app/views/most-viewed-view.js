define([
    'oroui/js/app/views/base/page-region-view'
], function(PageRegionView) {
    'use strict';

    var MostViewedView;

    MostViewedView = PageRegionView.extend({
        template: function() {},
        pageItems: [],
        dataItems: null,

        initialize: function(options) {
            this.dataItems = options.dataItems || 'mostviewed';
            this.pageItems = [this.dataItems];

            var self = this;
            this.template = function(data) {
                return data[self.dataItems];
            };

            MostViewedView.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            // does not update view is data is from cache
            if (!this.actionArgs || this.actionArgs.options.fromCache === true) {
                return this;
            }

            return MostViewedView.__super__.render.call(this);
        }
    });

    return MostViewedView;
});
