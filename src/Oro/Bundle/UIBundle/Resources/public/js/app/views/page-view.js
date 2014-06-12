/*global define*/
define([
    './base/view'
], function (BaseView) {
    'use strict';

    var PageView = BaseView.extend({
        el: 'body',
        regions: {
            mainContainer: '#container',
            mainMenu: '#main-menu',
            userMenu: '#top-page .user-menu',
            breadcrumb: '#breadcrumb'
        }
    });

    return PageView;
});
