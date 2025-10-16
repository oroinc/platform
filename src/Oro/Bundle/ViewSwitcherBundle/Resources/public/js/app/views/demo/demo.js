import _ from 'underscore';
import $ from 'jquery';
import DeviceSwitcherView from 'oroviewswitcher/js/app/views/device-switcher-view';
import innerPageModelService from 'oroviewswitcher/js/app/services/inner-page-model-service';
import DemoPopupView from 'oroviewswitcher/js/app/views/demo/demo-popup-view';
import DemoHelpCarouselView from 'oroviewswitcher/js/app/views/demo/demo-help-carousel-view';
import DemoLogoutButtonView from 'oroviewswitcher/js/app/views/demo/demo-logout-button-view';

require('app-modules').default;

import moduleConfig from 'module-config';
const config = moduleConfig(module.id);

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

export default DeviceSwitcherApp;
