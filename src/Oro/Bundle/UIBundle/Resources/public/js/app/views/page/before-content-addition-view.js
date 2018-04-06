define(['./../base/page-region-view'
], function(PageRegionView) {
    'use strict';

    var PageBeforeContentAdditionView;

    PageBeforeContentAdditionView = PageRegionView.extend({
        pageItems: ['beforeContentAddition'],

        /**
         * @inheritDoc
         */
        constructor: function PageBeforeContentAdditionView() {
            PageBeforeContentAdditionView.__super__.constructor.apply(this, arguments);
        },

        template: function(data) {
            return data.beforeContentAddition;
        },

        render: function() {
            PageBeforeContentAdditionView.__super__.render.call(this);

            if (this.data) {
                this.initLayout();
            }
            return this;
        }
    });

    return PageBeforeContentAdditionView;
});
