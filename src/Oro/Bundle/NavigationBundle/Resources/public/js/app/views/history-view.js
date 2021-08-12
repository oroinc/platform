define(function(require) {
    'use strict';

    const PageRegionView = require('oroui/js/app/views/base/page-region-view');

    const HistoryView = PageRegionView.extend({
        template: function() {},
        pageItems: [],
        dataItems: null,

        /**
         * @inheritdoc
         */
        constructor: function HistoryView(options) {
            HistoryView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.dataItems = options.dataItems || 'history';
            this.pageItems = [this.dataItems];

            const self = this;
            this.template = function(data) {
                return data[self.dataItems];
            };

            HistoryView.__super__.initialize.call(this, options);
        },

        render: function() {
            // does not update view if data is from cache
            if (!this.actionArgs || this.actionArgs.options.fromCache === true) {
                return this;
            }

            return HistoryView.__super__.render.call(this);
        }
    });

    return HistoryView;
});
