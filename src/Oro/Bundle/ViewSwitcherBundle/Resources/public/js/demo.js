define(function(require, exports, module) {
    'use strict';

    require('oroui/js/extend/polyfill');
    require('app-modules!');

    var DemoApp;
    var _ = require('underscore');
    var $ = require('jquery');
    var state = require('oroviewswitcher/js/state');
    var DemoPageComponent = require('oroviewswitcher/js/app/components/demo-page-component');
    var demoPageModelService = require('oroviewswitcher/js/app/services/demo-page-model-service');
    var DemoPopupView = require('oroviewswitcher/js/app/views/demo-popup-view');
    var DemoHelpCarouselView = require('oroviewswitcher/js/app/views/demo-help-carousel-view');

    var config = module.config();
    var pageModel = demoPageModelService.getModel();
    pageModel.set(_.pick(config, 'personalDemoUrl', 'projectName', 'styleMode'));

    DemoApp = new DemoPageComponent({
        _sourceElement: $('<div class="demo-page" />').appendTo('body'),
        state: state,
        pageModel: pageModel
    });

    DemoApp.demoHelpCarouselView = new DemoHelpCarouselView({
        container: '.head-panel__col--center',
        model: pageModel
    });

    if (pageModel.get('isLoggedIn')) {
        DemoApp.demoHelpCarouselView.openIfApplicable();
    } else {
        pageModel.once('change:isLoggedIn', function() {
            DemoApp.demoHelpCarouselView.openIfApplicable();
        });
    }

    if (DemoPopupView.isApplicable()) {
        DemoApp.demoPopupView = new DemoPopupView({
            container: 'body',
            url: pageModel.get('personalDemoUrl')
        });
    }

    return DemoApp;
});
