/*jslint nomen:true*/
/*global define*/
define([
    './../base/page-region-view'
], function (PageRegionView) {
    'use strict';

    var PageMainMenuView;

    PageMainMenuView = PageRegionView.extend({
        template: function (data) {
            return data.mainMenu;
        },
        pageItems: ['mainMenu'],

        render: function () {
            PageMainMenuView.__super__.render.call(this);
            this.$el.trigger('mainMenuUpdated');
        }
    });

    return PageMainMenuView;
});
