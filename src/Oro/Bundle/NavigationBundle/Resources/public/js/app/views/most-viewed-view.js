define(function(require) {
    'use strict';

    var MostViewedView;
    var PageRegionView = require('oroui/js/app/views/base/page-region-view');

    MostViewedView = PageRegionView.extend({
        template: function() {},
        pageItems: [],
        dataItems: null,

        /**
         * @inheritDoc
         */
        constructor: function MostViewedView() {
            MostViewedView.__super__.constructor.apply(this, arguments);
        },

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
