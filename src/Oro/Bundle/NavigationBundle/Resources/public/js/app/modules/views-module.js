define([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/tools'
], function(BaseController, tools) {
    'use strict';

    /**
     * Init ShortcutsView
     */
    BaseController.loadBeforeAction([
        'oronavigation/js/app/views/shortcuts-view'
    ], function(ShortcutsView) {
        BaseController.addToReuse('shortcuts', ShortcutsView, {
            el: '.shortcuts .input'
        });
    });

    // following functionality is related only to desktop version

    if (tools.isMobile()) {
        return;
    }

    /**
     * Init PageHistoryView
     */
    BaseController.loadBeforeAction([
        'oronavigation/js/app/views/history-view'
    ], function(PageHistoryView) {
        BaseController.addToReuse('history', PageHistoryView, {
            el: '#history-content'
        });
    });

    /**
     * Init PageMostViewedView
     */
    BaseController.loadBeforeAction([
        'oronavigation/js/app/views/most-viewed-view'
    ], function(PageMostViewedView) {
        BaseController.addToReuse('mostViewed', PageMostViewedView, {
            el: '#mostviewed-content'
        });
    });

    /**
     * Init Favorite related views
     */
    BaseController.loadBeforeAction([
        'jquery',
        'oronavigation/js/app/components/favorite-component',
        'oronavigation/js/app/models/base/model',
        'oronavigation/js/app/models/base/collection'
    ], function($, FavoriteComponent, Model, Collection) {
        var collection;

        collection = new Collection([], {
            model: Model
        });

        BaseController.addToReuse('favoritePage', FavoriteComponent, {
            dataSource: '#favorite-content [data-data]',
            buttonOptions: {
                el: '#bookmark-buttons .favorite-button',
                navigationElementType: 'favoriteButton'
            },
            tabItemTemplate: $('#template-dot-menu-item').html(),
            tabOptions: {
                el: '#favorite-content',
                listSelector: '.extra-list',
                fallbackSelector: '.dot-menu-empty-message'
            },
            collection: collection
        });
    });

    BaseController.loadBeforeAction([
        'jquery',
        'oronavigation/js/app/components/pin-component',
        'oronavigation/js/app/models/base/model',
        'oronavigation/js/app/models/base/collection',
        'oronavigation/js/app/views/page-state-view',
        'oronavigation/js/app/models/page-state-model'
    ], function($, PinComponent, Model, Collection, PageStateView, PageStateModel) {
        var stateModel;
        var template = $('#template-list-pin-item').html();
        var pinCollection = new Collection([], {
            model: Model
        });

        /**
         * Init PinBar related views
         */
        BaseController.addToReuse('pagePin', PinComponent, {
            dataSource: '#pinbar [data-data]',
            buttonOptions: {
                el: '#bookmark-buttons .minimize-button',
                navigationElementType: 'pinButton'
            },
            dropdownItemTemplate: template,
            dropdownOptions: {
                el: '#pinbar .show-more',
                listSelector: '.dropdown-menu ul'
            },
            barItemTemplate: template,
            barOptions: {
                el: '#pinbar .list-bar',
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
