import loadModules from 'oroui/js/app/services/load-modules';

const polyfills = [];

if (!window.fetch) {
    polyfills.push(loadModules('whatwg-fetch'));
}

export default polyfills;
