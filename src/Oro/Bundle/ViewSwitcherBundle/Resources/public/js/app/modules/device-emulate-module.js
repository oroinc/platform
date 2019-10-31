define(function(require, exports, module) {
    'use strict';

    // This app module enable the device-switcher in case when url has the device-emulate param
    // For example: http://localhost/?device-emulate=true

    const $ = require('jquery');
    const DeviceSwitcherView = require('oroviewswitcher/js/app/views/device-switcher-view');
    const innerPageModelService = require('oroviewswitcher/js/app/services/inner-page-model-service');
    const tools = require('oroui/js/tools');
    const config = require('module-config').default(module.id);
    const pageModel = innerPageModelService.getModel();

    pageModel.set({
        needHelp: null,
        personalDemoUrl: null
    });

    const params = tools.unpackFromQueryString(location.search);

    if (params['device-emulate'] && !window.frameElement) {
        document.body.innerHTML = '';

        new DeviceSwitcherView({
            _sourceElement: $('<div class="demo-page" />').appendTo('body'),
            pageModel: pageModel,
            switcherStyle: config.stylePath || '/css/themes/oro/view-switcher.css',
            updateUrlDeviceFragment: false
        });
    }
});
