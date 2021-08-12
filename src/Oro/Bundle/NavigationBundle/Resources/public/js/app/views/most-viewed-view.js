define(function(require) {
    'use strict';

    const PageRegionView = require('oroui/js/app/views/base/page-region-view');

    const MostViewedView = PageRegionView.extend({
        template: function() {},
        pageItems: [],
        dataItems: null,

        /**
         * @inheritdoc
         */
        constructor: function MostViewedView(options) {
            MostViewedView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            this.dataItems = options.dataItems || 'mostviewed';
            this.pageItems = [this.dataItems];

            const self = this;
            this.template = function(data) {
                return data[self.dataItems];
            };

            MostViewedView.__super__.initialize.call(this, options);
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
