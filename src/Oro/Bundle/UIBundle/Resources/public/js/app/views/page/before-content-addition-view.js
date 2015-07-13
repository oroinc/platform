define(['./../base/page-region-view'],
function(PageRegionView) {
    'use strict';

    var PageBeforeContentAdditionView;

    PageBeforeContentAdditionView = PageRegionView.extend({
        pageItems: ['beforeContentAddition'],

        template: function(data) {
            return data.beforeContentAddition;
        }
    });

    return PageBeforeContentAdditionView;
});
