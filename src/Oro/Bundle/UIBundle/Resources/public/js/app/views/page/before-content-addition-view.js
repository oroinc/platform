define(['./../base/page-region-view'],
function(PageRegionView) {
    'use strict';

    var PageBeforeContentAdditionView;

    PageBeforeContentAdditionView = PageRegionView.extend({
        pageItems: ['beforeContentAddition'],

        template: function(data) {
            return data.beforeContentAddition;
        },

        render: function() {
            PageBeforeContentAdditionView.__super__.render.call(this);
            this.initLayout();
            return this;
        }
    });

    return PageBeforeContentAdditionView;
});
