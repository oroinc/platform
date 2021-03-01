define(function(require, exports, module) {
    'use strict';

    require('app-modules').default;

    const _ = require('underscore');
    const $ = require('jquery');
    const DeviceSwitcherView = require('oroviewswitcher/js/app/views/device-switcher-view');
    const innerPageModelService = require('oroviewswitcher/js/app/services/inner-page-model-service');
    const DemoPopupView = require('oroviewswitcher/js/app/views/demo/demo-popup-view');
    const DemoHelpCarouselView = require('oroviewswitcher/js/app/views/demo/demo-help-carousel-view');
    const DemoLogoutButtonView = require('oroviewswitcher/js/app/views/demo/demo-logout-button-view');
    const config = require('module-config').default(module.id);

    const pageModel = innerPageModelService.getModel();
    pageModel.set(_.pick(config, 'personalDemoUrl', 'projectName'));

    if (config.translations) {
        window.Translator.fromJSON(config.translations);
    }

    const DeviceSwitcherApp = new DeviceSwitcherView({
        _sourceElement: $('<div class="demo-page" />').appendTo('body'),
        pageModel: pageModel
    });

    DeviceSwitcherApp.demoHelpCarouselView = new DemoHelpCarouselView({
        container: '.head-panel__col--center',
        model: pageModel
    });

    DeviceSwitcherApp.pageView.subview('logoutButton', new DemoLogoutButtonView({
        el: DeviceSwitcherApp.pageView.$('[data-role="control-action"]'),
        model: pageModel
    }));

    if (pageModel.get('isLoggedIn')) {
        DeviceSwitcherApp.demoHelpCarouselView.openIfApplicable();
    } else {
        pageModel.once('change:isLoggedIn', function() {
            DeviceSwitcherApp.demoHelpCarouselView.openIfApplicable();
        });
    }

    if (DemoPopupView.isApplicable()) {
        DeviceSwitcherApp.demoPopupView = new DemoPopupView({
            container: 'body',
            url: pageModel.get('personalDemoUrl')
        });
    }

    return DeviceSwitcherApp;
});
