define([
    './../base/page-region-view'
], function(PageRegionView) {
    'use strict';

    const PageUserMenuView = PageRegionView.extend({
        template: function(data) {
            return data.usermenu;
        },

        pageItems: ['usermenu'],

        /**
         * @inheritdoc
         */
        constructor: function PageUserMenuView(options) {
            PageUserMenuView.__super__.constructor.call(this, options);
        }
    });

    return PageUserMenuView;
});
