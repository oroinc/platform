const config = require('module-config').default(module.id);
const scriptjs = require('scriptjs');
let scriptjsExtend = scriptjs;

/**
 * Overrides original scriptjs loader to resolve path to local modules before loading
 * (Local modules are considered paths, that don't start with 'https://', 'http://' or '//')
 */
if ('bundlesPath' in config) {
    const scriptpath = config.bundlesPath;

    scriptjsExtend = function $script(paths, idOrDone, optDone) {
        paths = Array.isArray(paths) ? paths : [paths];
        paths = paths.map(function(path) {
            if (!/^(?:https?:)?\/\//.test(path)) {
                path = path.indexOf('.js') === -1 ? scriptpath + path + '.js' : scriptpath + path;
            }
            return path;
        });

        scriptjs(paths, idOrDone, optDone);
    };

    Object.assign(scriptjsExtend, scriptjs);

    scriptjsExtend.order = function(scripts, id, done) {
        (function callback(s) {
            s = scripts.shift();
            !scripts.length ? scriptjsExtend(s, id, done) : scriptjsExtend(s, callback);
        }());
    };

    scriptjsExtend.ready = function(deps, ready, req) {
        scriptjs.ready(deps, ready, req);
        return scriptjsExtend;
    };
}

module.exports = scriptjsExtend;
