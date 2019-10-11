define(function(require, exports, module) {
    'use strict';

    // This app module enable the device-switcher in case when url has the device-emulate param
    // For example: http://localhost/?device-emulate=true

    var $ = require('jquery');
    var DeviceSwitcherView = require('oroviewswitcher/js/app/views/device-switcher-view');
    var innerPageModelService = require('oroviewswitcher/js/app/services/inner-page-model-service');
    var tools = require('oroui/js/tools');
    var config = module.config();
    var pageModel = innerPageModelService.getModel();

    pageModel.set({
        needHelp: null,
        personalDemoUrl: null
    });

    var params = tools.unpackFromQueryString(location.search);

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
