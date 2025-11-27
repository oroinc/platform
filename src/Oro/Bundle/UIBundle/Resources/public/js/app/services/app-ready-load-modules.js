import loadModules from 'oroui/js/app/services/load-modules';
import appReadyPromise from 'oroui/js/app';

export default function appReadyLoadModules(modules, callback, context) {
    return appReadyPromise.then(function() {
        return loadModules(modules, callback, context);
    });
};
