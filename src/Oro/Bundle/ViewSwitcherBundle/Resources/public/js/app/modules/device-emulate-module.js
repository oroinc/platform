// This app module enable the device-switcher in case when url has the device-emulate param
// For example: http://localhost/?device-emulate=true

import loadModules from 'oroui/js/app/services/load-modules';
import tools from 'oroui/js/tools';
import moduleConfig from 'module-config';

const params = tools.unpackFromQueryString(location.search);
let promise = null;

if (params['device-emulate'] && !window.frameElement) {
    document.body.innerHTML = '';

    const config = moduleConfig(module.id);
    promise = loadModules([
        'oroviewswitcher/js/app/views/device-switcher-view',
        'oroviewswitcher/js/app/services/inner-page-model-service'
    ]).then(([DeviceSwitcherView, innerPageModelService]) => {
        const pageModel = innerPageModelService.getModel();

        pageModel.set({
            needHelp: null,
            personalDemoUrl: null
        });

        const elem = document.createElement('div');
        elem.classList.add('demo-page');
        document.querySelector('body').appendChild(elem);

        new DeviceSwitcherView({
            _sourceElement: [elem],
            pageModel: pageModel,
            switcherStyle: config.stylePath || '/build/view-switcher/css/view-switcher.css',
            updateUrlDeviceFragment: false
        });
    });
}

export default promise;
