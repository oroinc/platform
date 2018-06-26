define(function(require) {
    'use strict';

    var HistoryView;
    var PageRegionView = require('oroui/js/app/views/base/page-region-view');

    HistoryView = PageRegionView.extend({
        template: function() {},
        pageItems: [],
        dataItems: null,

        /**
         * @inheritDoc
         */
        constructor: function HistoryView() {
            HistoryView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.dataItems = options.dataItems || 'history';
            this.pageItems = [this.dataItems];

            var self = this;
            this.template = function(data) {
                return data[self.dataItems];
            };

            HistoryView.__super__.initialize.apply(this, arguments);
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
