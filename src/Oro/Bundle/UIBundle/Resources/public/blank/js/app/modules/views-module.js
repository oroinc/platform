define(function(require) {
    'use strict';

    var BaseController = require('oroui/js/app/controllers/base/controller');
    var PageView = require('oroui/js/app/views/page-view');
    var module = require('module');
    var _ = require('underscore');

    var config = module.config();
    config = _.extend({
        showLoadingMaskOnStartup: true
    }, config);

    /**
     * Init PageView
     */
    BaseController.addToReuse('page', PageView, {
        el: 'body',
        keepElement: true,
        regions: {
            mainContainer: '#container'
        }
    });

    /**
     * Init PageContentView
     */
    BaseController.loadBeforeAction([
        'oroui/js/app/views/page/content-view'
    ], function(PageContentView) {
        BaseController.addToReuse('content', PageContentView, {
            el: 'region:mainContainer'
        });
    });
});
