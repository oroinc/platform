define(function(require) {
    'use strict';

    var $ = require('jquery');
    var DeviceSwitcherView = require('oroviewswitcher/js/app/views/device-switcher-view');
    var innerPageModelService = require('oroviewswitcher/js/app/services/inner-page-model-service');
    var tools = require('oroui/js/tools');

    var pageModel = innerPageModelService.getModel();
    pageModel.set({
        needHelp: null,
        personalDemoUrl: null
    });

    var params = tools.unpackFromQueryString(location.search);

    if (params['device-emulate'] && !window.frameElement) {
        document.body.innerHTML = '';

        var viewStyle = document.createElement('link');

        viewStyle.href = '/css/themes/oro/view-switcher.css';
        viewStyle.rel = 'stylesheet';

        document.head.appendChild(viewStyle);

        new DeviceSwitcherView({
            _sourceElement: $('<div class="demo-page" />').appendTo('body'),
            pageModel: pageModel,
            updateUrlDeviceFragment: false
        });
    }
});
