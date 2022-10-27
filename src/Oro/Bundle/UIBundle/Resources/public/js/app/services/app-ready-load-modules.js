const loadModules = require( 'oroui/js/app/services/load-modules');
const appReadyPromise = require( 'oroui/js/app');

module.exports = function appReadyLoadModules(modules, callback, context) {
    return appReadyPromise.then(function() {
        return loadModules(modules, callback, context);
    });
};
