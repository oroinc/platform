/*global require*/
require([
    'oroui/js/app/controllers/base/controller'
], function (BaseController) {
    'use strict';

    /**
     * Init PageHistoryView
     */
    BaseController.loadBeforeAction([
        'oronavigation/js/app/views/history-view'
    ], function (PageHistoryView) {
        BaseController.addToReuse('history', PageHistoryView, {
            el: '#history-content'
        });
    });

    /**
     * Init PageMostViewedView
     */
    BaseController.loadBeforeAction([
        'oronavigation/js/app/views/most-viewed-view'
    ], function (PageMostViewedView) {
        BaseController.addToReuse('mostViewed', PageMostViewedView, {
            el: '#mostviewed-content'
        });
    });

    /**
     * Init Favorite related views
     */
    BaseController.loadBeforeAction([
        'jquery',
        'oronavigation/js/app/views/favorite/main-view',
        'oronavigation/js/app/models/base/model',
        'oronavigation/js/app/models/base/collection'
    ], function ($, FavoriteView, Model, Collection) {
        var collection;

        collection = new Collection([], {
            model: Model
        });

        BaseController.addToReuse('pageFavorite', FavoriteView, {
            el: 'body',
            keepElement: true,
            dataSource: '#favorite-content [data-data]',
            regions: {
                pinButton: '#pin-button-div .favorite-button',
                pinTab: '#favorite-content'
            },
            tabItemTemplate: $('#template-dot-menu-item').html(),
            tabOptions: {
                listSelector: '.extra-list',
                fallbackSelector: '.dot-menu-empty-message'
            },
            collection: collection
        });
    });

    BaseController.loadBeforeAction([
        'jquery',
        'oronavigation/js/app/views/pin/main-view',
        'oronavigation/js/app/models/base/model',
        'oronavigation/js/app/models/base/collection',
        'oronavigation/js/app/views/page-state-view',
        'oronavigation/js/app/models/page-state-model'
    ], function ($, PinView, Model, Collection, PageStateView, PageStateModel) {
        var pinCollection, stateModel;

        pinCollection = new Collection([], {
            model: Model
        });

        /**
         * Init PinBar related views
         */
        BaseController.addToReuse('pagePin', PinView, {
            el: 'body',
            keepElement: true,
            dataSource: '#pinbar-content [data-data]',
            regions: {
                pinButton: '#pin-button-div .minimize-button',
                pinTab: '#pinbar-content',
                pinBar: '.list-bar'
            },
            tabItemTemplate: $('#template-dot-menu-item').html(),
            tabOptions: {
                listSelector: '.extra-list',
                fallbackSelector: '.dot-menu-empty-message'
            },
            barItemTemplate: $('#template-list-pin-item').html(),
            barOptions: {
                listSelector: 'ul',
                fallbackSelector: '.pin-bar-empty'
            },
            collection: pinCollection
        });

        /**
         * Init PageState view
         */
        stateModel = new PageStateModel();
        BaseController.addToReuse('pageState', PageStateView, {
            el: '#container',
            keepElement: true,
            model: stateModel,
            collection: pinCollection
        });
    });
});
