/**
 * @license r.js 2.1.15 Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
 * Available via the MIT or new BSD license.
 * see: http://github.com/jrburke/requirejs for details
 */

/*
 * This is a bootstrap script to allow running RequireJS in the command line
 * in either a Java/Rhino or Node environment. It is modified by the top-level
 * dist.js file to inject other files to completely enable this file. It is
 * the shell of the r.js file.
 */

/*jslint evil: true, nomen: true, sloppy: true */
/*global readFile: true, process: false, Packages: false, print: false,
 console: false, java: false, module: false, requirejsVars, navigator,
 document, importScripts, self, location, Components, FileUtils */

var requirejs, require, define, xpcUtil;
(function (console, args, readFileFunc) {
    var fileName, env, fs, vm, path, exec, rhinoContext, dir, nodeRequire,
        nodeDefine, exists, reqMain, loadedOptimizedLib, existsForNode, Cc, Ci,
        version = '2.1.15',
        jsSuffixRegExp = /\.js$/,
        commandOption = '',
        useLibLoaded = {},
    //Used by jslib/rhino/args.js
        rhinoArgs = args,
    //Used by jslib/xpconnect/args.js
        xpconnectArgs = args,
        readFile = typeof readFileFunc !== 'undefined' ? readFileFunc : null;

    function showHelp() {
        console.log('See https://github.com/jrburke/r.js for usage.');
    }

    if ((typeof navigator !== 'undefined' && typeof document !== 'undefined') ||
        (typeof importScripts !== 'undefined' && typeof self !== 'undefined')) {
        env = 'browser';

        readFile = function (path) {
            return fs.readFileSync(path, 'utf8');
        };

        exec = function (string) {
            return eval(string);
        };

        exists = function () {
            console.log('x.js exists not applicable in browser env');
            return false;
        };

    } else if (typeof process !== 'undefined' && process.versions && !!process.versions.node) {
        env = 'node';

        //Get the fs module via Node's require before it
        //gets replaced. Used in require/node.js
        fs = require('fs');
        vm = require('vm');
        path = require('path');
        //In Node 0.7+ existsSync is on fs.
        existsForNode = fs.existsSync || path.existsSync;

        nodeRequire = require;
        nodeDefine = define;
        reqMain = require.main;

        //Temporarily hide require and define to allow require.js to define
        //them.
        require = undefined;
        define = undefined;

        readFile = function (path) {
            return fs.readFileSync(path, 'utf8');
        };

        exec = function (string, name) {
            return vm.runInThisContext(this.requirejsVars.require.makeNodeWrapper(string),
                name ? fs.realpathSync(name) : '');
        };

        exists = function (fileName) {
            return existsForNode(fileName);
        };


        fileName = process.argv[2];

        if (fileName && fileName.indexOf('-') === 0) {
            commandOption = fileName.substring(1);
            fileName = process.argv[3];
        }
    } else if (typeof Packages !== 'undefined') {
        env = 'rhino';

        fileName = args[0];

        if (fileName && fileName.indexOf('-') === 0) {
            commandOption = fileName.substring(1);
            fileName = args[1];
        }

        //Set up execution context.
        rhinoContext = Packages.org.mozilla.javascript.ContextFactory.getGlobal().enterContext();

        exec = function (string, name) {
            return rhinoContext.evaluateString(this, string, name, 0, null);
        };

        exists = function (fileName) {
            return (new java.io.File(fileName)).exists();
        };

        //Define a console.log for easier logging. Don't
        //get fancy though.
        if (typeof console === 'undefined') {
            console = {
                log: function () {
                    print.apply(undefined, arguments);
                }
            };
        }
    } else if (typeof Components !== 'undefined' && Components.classes && Components.interfaces) {
        env = 'xpconnect';

        Components.utils['import']('resource://gre/modules/FileUtils.jsm');
        Cc = Components.classes;
        Ci = Components.interfaces;

        fileName = args[0];

        if (fileName && fileName.indexOf('-') === 0) {
            commandOption = fileName.substring(1);
            fileName = args[1];
        }

        xpcUtil = {
            isWindows: ('@mozilla.org/windows-registry-key;1' in Cc),
            cwd: function () {
                return FileUtils.getFile("CurWorkD", []).path;
            },

            //Remove . and .. from paths, normalize on front slashes
            normalize: function (path) {
                //There has to be an easier way to do this.
                var i, part, ary,
                    firstChar = path.charAt(0);

                if (firstChar !== '/' &&
                    firstChar !== '\\' &&
                    path.indexOf(':') === -1) {
                    //A relative path. Use the current working directory.
                    path = xpcUtil.cwd() + '/' + path;
                }

                ary = path.replace(/\\/g, '/').split('/');

                for (i = 0; i < ary.length; i += 1) {
                    part = ary[i];
                    if (part === '.') {
                        ary.splice(i, 1);
                        i -= 1;
                    } else if (part === '..') {
                        ary.splice(i - 1, 2);
                        i -= 2;
                    }
                }
                return ary.join('/');
            },

            xpfile: function (path) {
                var fullPath;
                try {
                    fullPath = xpcUtil.normalize(path);
                    if (xpcUtil.isWindows) {
                        fullPath = fullPath.replace(/\//g, '\\');
                    }
                    return new FileUtils.File(fullPath);
                } catch (e) {
                    throw new Error((fullPath || path) + ' failed: ' + e);
                }
            },

            readFile: function (/*String*/path, /*String?*/encoding) {
                //A file read function that can deal with BOMs
                encoding = encoding || "utf-8";

                var inStream, convertStream,
                    readData = {},
                    fileObj = xpcUtil.xpfile(path);

                //XPCOM, you so crazy
                try {
                    inStream = Cc['@mozilla.org/network/file-input-stream;1']
                        .createInstance(Ci.nsIFileInputStream);
                    inStream.init(fileObj, 1, 0, false);

                    convertStream = Cc['@mozilla.org/intl/converter-input-stream;1']
                        .createInstance(Ci.nsIConverterInputStream);
                    convertStream.init(inStream, encoding, inStream.available(),
                        Ci.nsIConverterInputStream.DEFAULT_REPLACEMENT_CHARACTER);

                    convertStream.readString(inStream.available(), readData);
                    return readData.value;
                } catch (e) {
                    throw new Error((fileObj && fileObj.path || '') + ': ' + e);
                } finally {
                    if (convertStream) {
                        convertStream.close();
                    }
                    if (inStream) {
                        inStream.close();
                    }
                }
            }
        };

        readFile = xpcUtil.readFile;

        exec = function (string) {
            return eval(string);
        };

        exists = function (fileName) {
            return xpcUtil.xpfile(fileName).exists();
        };

        //Define a console.log for easier logging. Don't
        //get fancy though.
        if (typeof console === 'undefined') {
            console = {
                log: function () {
                    print.apply(undefined, arguments);
                }
            };
        }
    }

    /** vim: et:ts=4:sw=4:sts=4
     * @license RequireJS 2.1.15 Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
     * Available via the MIT or new BSD license.
     * see: http://github.com/jrburke/requirejs for details
     */
//Not using strict: uneven strict support in browsers, #392, and causes
//problems with requirejs.exec()/transpiler plugins that may not be strict.
    /*jslint regexp: true, nomen: true, sloppy: true */
    /*global window, navigator, document, importScripts, setTimeout, opera */


    (function (global) {
        var req, s, head, baseElement, dataMain, src,
            interactiveScript, currentlyAddingScript, mainScript, subPath,
            version = '2.1.15',
            commentRegExp = /(\/\*([\s\S]*?)\*\/|([^:]|^)\/\/(.*)$)/mg,
            cjsRequireRegExp = /[^.]\s*require\s*\(\s*["']([^'"\s]+)["']\s*\)/g,
            jsSuffixRegExp = /\.js$/,
            currDirRegExp = /^\.\//,
            op = Object.prototype,
            ostring = op.toString,
            hasOwn = op.hasOwnProperty,
            ap = Array.prototype,
            apsp = ap.splice,
            isBrowser = !!(typeof window !== 'undefined' && typeof navigator !== 'undefined' && window.document),
            isWebWorker = !isBrowser && typeof importScripts !== 'undefined',
        //PS3 indicates loaded and complete, but need to wait for complete
        //specifically. Sequence is 'loading', 'loaded', execution,
        // then 'complete'. The UA check is unfortunate, but not sure how
        //to feature test w/o causing perf issues.
            readyRegExp = isBrowser && navigator.platform === 'PLAYSTATION 3' ?
                /^complete$/ : /^(complete|loaded)$/,
            defContextName = '_',
        //Oh the tragedy, detecting opera. See the usage of isOpera for reason.
            isOpera = typeof opera !== 'undefined' && opera.toString() === '[object Opera]',
            contexts = {},
            cfg = {},
            globalDefQueue = [],
            useInteractive = false;

        function isFunction(it) {
            return ostring.call(it) === '[object Function]';
        }

        function isArray(it) {
            return ostring.call(it) === '[object Array]';
        }

        /**
         * Helper function for iterating over an array. If the func returns
         * a true value, it will break out of the loop.
         */
        function each(ary, func) {
            if (ary) {
                var i;
                for (i = 0; i < ary.length; i += 1) {
                    if (ary[i] && func(ary[i], i, ary)) {
                        break;
                    }
                }
            }
        }

        /**
         * Helper function for iterating over an array backwards. If the func
         * returns a true value, it will break out of the loop.
         */
        function eachReverse(ary, func) {
            if (ary) {
                var i;
                for (i = ary.length - 1; i > -1; i -= 1) {
                    if (ary[i] && func(ary[i], i, ary)) {
                        break;
                    }
                }
            }
        }

        function hasProp(obj, prop) {
            return hasOwn.call(obj, prop);
        }

        function getOwn(obj, prop) {
            return hasProp(obj, prop) && obj[prop];
        }

        /**
         * Cycles over properties in an object and calls a function for each
         * property value. If the function returns a truthy value, then the
         * iteration is stopped.
         */
        function eachProp(obj, func) {
            var prop;
            for (prop in obj) {
                if (hasProp(obj, prop)) {
                    if (func(obj[prop], prop)) {
                        break;
                    }
                }
            }
        }

        /**
         * Simple function to mix in properties from source into target,
         * but only if target does not already have a property of the same name.
         */
        function mixin(target, source, force, deepStringMixin) {
            if (source) {
                eachProp(source, function (value, prop) {
                    if (force || !hasProp(target, prop)) {
                        if (deepStringMixin && typeof value === 'object' && value &&
                            !isArray(value) && !isFunction(value) &&
                            !(value instanceof RegExp)) {

                            if (!target[prop]) {
                                target[prop] = {};
                            }
                            mixin(target[prop], value, force, deepStringMixin);
                        } else {
                            target[prop] = value;
                        }
                    }
                });
            }
            return target;
        }

        //Similar to Function.prototype.bind, but the 'this' object is specified
        //first, since it is easier to read/figure out what 'this' will be.
        function bind(obj, fn) {
            return function () {
                return fn.apply(obj, arguments);
            };
        }

        function scripts() {
            return document.getElementsByTagName('script');
        }

        function defaultOnError(err) {
            throw err;
        }

        //Allow getting a global that is expressed in
        //dot notation, like 'a.b.c'.
        function getGlobal(value) {
            if (!value) {
                return value;
            }
            var g = global;
            each(value.split('.'), function (part) {
                g = g[part];
            });
            return g;
        }

        /**
         * Constructs an error with a pointer to an URL with more information.
         * @param {String} id the error ID that maps to an ID on a web page.
         * @param {String} message human readable error.
         * @param {Error} [err] the original error, if there is one.
         *
         * @returns {Error}
         */
        function makeError(id, msg, err, requireModules) {
            var e = new Error(msg + '\nhttp://requirejs.org/docs/errors.html#' + id);
            e.requireType = id;
            e.requireModules = requireModules;
            if (err) {
                e.originalError = err;
            }
            return e;
        }

        if (typeof define !== 'undefined') {
            //If a define is already in play via another AMD loader,
            //do not overwrite.
            return;
        }

        if (typeof requirejs !== 'undefined') {
            if (isFunction(requirejs)) {
                //Do not overwrite an existing requirejs instance.
                return;
            }
            cfg = requirejs;
            requirejs = undefined;
        }

        //Allow for a require config object
        if (typeof require !== 'undefined' && !isFunction(require)) {
            //assume it is a config object.
            cfg = require;
            require = undefined;
        }

        function newContext(contextName) {
            var inCheckLoaded, Module, context, handlers,
                checkLoadedTimeoutId,
                config = {
                    //Defaults. Do not set a default for map
                    //config to speed up normalize(), which
                    //will run faster if there is no default.
                    waitSeconds: 7,
                    baseUrl: './',
                    paths: {},
                    bundles: {},
                    pkgs: {},
                    shim: {},
                    config: {}
                },
                registry = {},
            //registry of just enabled modules, to speed
            //cycle breaking code when lots of modules
            //are registered, but not activated.
                enabledRegistry = {},
                undefEvents = {},
                defQueue = [],
                defined = {},
                urlFetched = {},
                bundlesMap = {},
                requireCounter = 1,
                unnormalizedCounter = 1;

            /**
             * Trims the . and .. from an array of path segments.
             * It will keep a leading path segment if a .. will become
             * the first path segment, to help with module name lookups,
             * which act like paths, but can be remapped. But the end result,
             * all paths that use this function should look normalized.
             * NOTE: this method MODIFIES the input array.
             * @param {Array} ary the array of path segments.
             */
            function trimDots(ary) {
                var i, part;
                for (i = 0; i < ary.length; i++) {
                    part = ary[i];
                    if (part === '.') {
                        ary.splice(i, 1);
                        i -= 1;
                    } else if (part === '..') {
                        // If at the start, or previous value is still ..,
                        // keep them so that when converted to a path it may
                        // still work when converted to a path, even though
                        // as an ID it is less than ideal. In larger point
                        // releases, may be better to just kick out an error.
                        if (i === 0 || (i == 1 && ary[2] === '..') || ary[i - 1] === '..') {
                            continue;
                        } else if (i > 0) {
                            ary.splice(i - 1, 2);
                            i -= 2;
                        }
                    }
                }
            }

            /**
             * Given a relative module name, like ./something, normalize it to
             * a real name that can be mapped to a path.
             * @param {String} name the relative name
             * @param {String} baseName a real name that the name arg is relative
             * to.
             * @param {Boolean} applyMap apply the map config to the value. Should
             * only be done if this normalization is for a dependency ID.
             * @returns {String} normalized name
             */
            function normalize(name, baseName, applyMap) {
                var pkgMain, mapValue, nameParts, i, j, nameSegment, lastIndex,
                    foundMap, foundI, foundStarMap, starI, normalizedBaseParts,
                    baseParts = (baseName && baseName.split('/')),
                    map = config.map,
                    starMap = map && map['*'];

                //Adjust any relative paths.
                if (name) {
                    name = name.split('/');
                    lastIndex = name.length - 1;

                    // If wanting node ID compatibility, strip .js from end
                    // of IDs. Have to do this here, and not in nameToUrl
                    // because node allows either .js or non .js to map
                    // to same file.
                    if (config.nodeIdCompat && jsSuffixRegExp.test(name[lastIndex])) {
                        name[lastIndex] = name[lastIndex].replace(jsSuffixRegExp, '');
                    }

                    // Starts with a '.' so need the baseName
                    if (name[0].charAt(0) === '.' && baseParts) {
                        //Convert baseName to array, and lop off the last part,
                        //so that . matches that 'directory' and not name of the baseName's
                        //module. For instance, baseName of 'one/two/three', maps to
                        //'one/two/three.js', but we want the directory, 'one/two' for
                        //this normalization.
                        normalizedBaseParts = baseParts.slice(0, baseParts.length - 1);
                        name = normalizedBaseParts.concat(name);
                    }

                    trimDots(name);
                    name = name.join('/');
                }

                //Apply map config if available.
                if (applyMap && map && (baseParts || starMap)) {
                    nameParts = name.split('/');

                    outerLoop: for (i = nameParts.length; i > 0; i -= 1) {
                        nameSegment = nameParts.slice(0, i).join('/');

                        if (baseParts) {
                            //Find the longest baseName segment match in the config.
                            //So, do joins on the biggest to smallest lengths of baseParts.
                            for (j = baseParts.length; j > 0; j -= 1) {
                                mapValue = getOwn(map, baseParts.slice(0, j).join('/'));

                                //baseName segment has config, find if it has one for
                                //this name.
                                if (mapValue) {
                                    mapValue = getOwn(mapValue, nameSegment);
                                    if (mapValue) {
                                        //Match, update name to the new value.
                                        foundMap = mapValue;
                                        foundI = i;
                                        break outerLoop;
                                    }
                                }
                            }
                        }

                        //Check for a star map match, but just hold on to it,
                        //if there is a shorter segment match later in a matching
                        //config, then favor over this star map.
                        if (!foundStarMap && starMap && getOwn(starMap, nameSegment)) {
                            foundStarMap = getOwn(starMap, nameSegment);
                            starI = i;
                        }
                    }

                    if (!foundMap && foundStarMap) {
                        foundMap = foundStarMap;
                        foundI = starI;
                    }

                    if (foundMap) {
                        nameParts.splice(0, foundI, foundMap);
                        name = nameParts.join('/');
                    }
                }

                // If the name points to a package's name, use
                // the package main instead.
                pkgMain = getOwn(config.pkgs, name);

                return pkgMain ? pkgMain : name;
            }

            function removeScript(name) {
                if (isBrowser) {
                    each(scripts(), function (scriptNode) {
                        if (scriptNode.getAttribute('data-requiremodule') === name &&
                            scriptNode.getAttribute('data-requirecontext') === context.contextName) {
                            scriptNode.parentNode.removeChild(scriptNode);
                            return true;
                        }
                    });
                }
            }

            function hasPathFallback(id) {
                var pathConfig = getOwn(config.paths, id);
                if (pathConfig && isArray(pathConfig) && pathConfig.length > 1) {
                    //Pop off the first array value, since it failed, and
                    //retry
                    pathConfig.shift();
                    context.require.undef(id);

                    //Custom require that does not do map translation, since
                    //ID is "absolute", already mapped/resolved.
                    context.makeRequire(null, {
                        skipMap: true
                    })([id]);

                    return true;
                }
            }

            //Turns a plugin!resource to [plugin, resource]
            //with the plugin being undefined if the name
            //did not have a plugin prefix.
            function splitPrefix(name) {
                var prefix,
                    index = name ? name.indexOf('!') : -1;
                if (index > -1) {
                    prefix = name.substring(0, index);
                    name = name.substring(index + 1, name.length);
                }
                return [prefix, name];
            }

            /**
             * Creates a module mapping that includes plugin prefix, module
             * name, and path. If parentModuleMap is provided it will
             * also normalize the name via require.normalize()
             *
             * @param {String} name the module name
             * @param {String} [parentModuleMap] parent module map
             * for the module name, used to resolve relative names.
             * @param {Boolean} isNormalized: is the ID already normalized.
             * This is true if this call is done for a define() module ID.
             * @param {Boolean} applyMap: apply the map config to the ID.
             * Should only be true if this map is for a dependency.
             *
             * @returns {Object}
             */
            function makeModuleMap(name, parentModuleMap, isNormalized, applyMap) {
                var url, pluginModule, suffix, nameParts,
                    prefix = null,
                    parentName = parentModuleMap ? parentModuleMap.name : null,
                    originalName = name,
                    isDefine = true,
                    normalizedName = '';

                //If no name, then it means it is a require call, generate an
                //internal name.
                if (!name) {
                    isDefine = false;
                    name = '_@r' + (requireCounter += 1);
                }

                nameParts = splitPrefix(name);
                prefix = nameParts[0];
                name = nameParts[1];

                if (prefix) {
                    prefix = normalize(prefix, parentName, applyMap);
                    pluginModule = getOwn(defined, prefix);
                }

                //Account for relative paths if there is a base name.
                if (name) {
                    if (prefix) {
                        if (pluginModule && pluginModule.normalize) {
                            //Plugin is loaded, use its normalize method.
                            normalizedName = pluginModule.normalize(name, function (name) {
                                return normalize(name, parentName, applyMap);
                            });
                        } else {
                            // If nested plugin references, then do not try to
                            // normalize, as it will not normalize correctly. This
                            // places a restriction on resourceIds, and the longer
                            // term solution is not to normalize until plugins are
                            // loaded and all normalizations to allow for async
                            // loading of a loader plugin. But for now, fixes the
                            // common uses. Details in #1131
                            normalizedName = name.indexOf('!') === -1 ?
                                normalize(name, parentName, applyMap) :
                                name;
                        }
                    } else {
                        //A regular module.
                        normalizedName = normalize(name, parentName, applyMap);

                        //Normalized name may be a plugin ID due to map config
                        //application in normalize. The map config values must
                        //already be normalized, so do not need to redo that part.
                        nameParts = splitPrefix(normalizedName);
                        prefix = nameParts[0];
                        normalizedName = nameParts[1];
                        isNormalized = true;

                        url = context.nameToUrl(normalizedName);
                    }
                }

                //If the id is a plugin id that cannot be determined if it needs
                //normalization, stamp it with a unique ID so two matching relative
                //ids that may conflict can be separate.
                suffix = prefix && !pluginModule && !isNormalized ?
                    '_unnormalized' + (unnormalizedCounter += 1) :
                    '';

                return {
                    prefix: prefix,
                    name: normalizedName,
                    parentMap: parentModuleMap,
                    unnormalized: !!suffix,
                    url: url,
                    originalName: originalName,
                    isDefine: isDefine,
                    id: (prefix ?
                        prefix + '!' + normalizedName :
                        normalizedName) + suffix
                };
            }

            function getModule(depMap) {
                var id = depMap.id,
                    mod = getOwn(registry, id);

                if (!mod) {
                    mod = registry[id] = new context.Module(depMap);
                }

                return mod;
            }

            function on(depMap, name, fn) {
                var id = depMap.id,
                    mod = getOwn(registry, id);

                if (hasProp(defined, id) &&
                    (!mod || mod.defineEmitComplete)) {
                    if (name === 'defined') {
                        fn(defined[id]);
                    }
                } else {
                    mod = getModule(depMap);
                    if (mod.error && name === 'error') {
                        fn(mod.error);
                    } else {
                        mod.on(name, fn);
                    }
                }
            }

            function onError(err, errback) {
                var ids = err.requireModules,
                    notified = false;

                if (errback) {
                    errback(err);
                } else {
                    each(ids, function (id) {
                        var mod = getOwn(registry, id);
                        if (mod) {
                            //Set error on module, so it skips timeout checks.
                            mod.error = err;
                            if (mod.events.error) {
                                notified = true;
                                mod.emit('error', err);
                            }
                        }
                    });

                    if (!notified) {
                        req.onError(err);
                    }
                }
            }

            /**
             * Internal method to transfer globalQueue items to this context's
             * defQueue.
             */
            function takeGlobalQueue() {
                //Push all the globalDefQueue items into the context's defQueue
                if (globalDefQueue.length) {
                    //Array splice in the values since the context code has a
                    //local var ref to defQueue, so cannot just reassign the one
                    //on context.
                    apsp.apply(defQueue,
                        [defQueue.length, 0].concat(globalDefQueue));
                    globalDefQueue = [];
                }
            }

            handlers = {
                'require': function (mod) {
                    if (mod.require) {
                        return mod.require;
                    } else {
                        return (mod.require = context.makeRequire(mod.map));
                    }
                },
                'exports': function (mod) {
                    mod.usingExports = true;
                    if (mod.map.isDefine) {
                        if (mod.exports) {
                            return (defined[mod.map.id] = mod.exports);
                        } else {
                            return (mod.exports = defined[mod.map.id] = {});
                        }
                    }
                },
                'module': function (mod) {
                    if (mod.module) {
                        return mod.module;
                    } else {
                        return (mod.module = {
                            id: mod.map.id,
                            uri: mod.map.url,
                            config: function () {
                                return  getOwn(config.config, mod.map.id) || {};
                            },
                            exports: mod.exports || (mod.exports = {})
                        });
                    }
                }
            };

            function cleanRegistry(id) {
                //Clean up machinery used for waiting modules.
                delete registry[id];
                delete enabledRegistry[id];
            }

            function breakCycle(mod, traced, processed) {
                var id = mod.map.id;

                if (mod.error) {
                    mod.emit('error', mod.error);
                } else {
                    traced[id] = true;
                    each(mod.depMaps, function (depMap, i) {
                        var depId = depMap.id,
                            dep = getOwn(registry, depId);

                        //Only force things that have not completed
                        //being defined, so still in the registry,
                        //and only if it has not been matched up
                        //in the module already.
                        if (dep && !mod.depMatched[i] && !processed[depId]) {
                            if (getOwn(traced, depId)) {
                                mod.defineDep(i, defined[depId]);
                                mod.check(); //pass false?
                            } else {
                                breakCycle(dep, traced, processed);
                            }
                        }
                    });
                    processed[id] = true;
                }
            }

            function checkLoaded() {
                var err, usingPathFallback,
                    waitInterval = config.waitSeconds * 1000,
                //It is possible to disable the wait interval by using waitSeconds of 0.
                    expired = waitInterval && (context.startTime + waitInterval) < new Date().getTime(),
                    noLoads = [],
                    reqCalls = [],
                    stillLoading = false,
                    needCycleCheck = true;

                //Do not bother if this call was a result of a cycle break.
                if (inCheckLoaded) {
                    return;
                }

                inCheckLoaded = true;

                //Figure out the state of all the modules.
                eachProp(enabledRegistry, function (mod) {
                    var map = mod.map,
                        modId = map.id;

                    //Skip things that are not enabled or in error state.
                    if (!mod.enabled) {
                        return;
                    }

                    if (!map.isDefine) {
                        reqCalls.push(mod);
                    }

                    if (!mod.error) {
                        //If the module should be executed, and it has not
                        //been inited and time is up, remember it.
                        if (!mod.inited && expired) {
                            if (hasPathFallback(modId)) {
                                usingPathFallback = true;
                                stillLoading = true;
                            } else {
                                noLoads.push(modId);
                                removeScript(modId);
                            }
                        } else if (!mod.inited && mod.fetched && map.isDefine) {
                            stillLoading = true;
                            if (!map.prefix) {
                                //No reason to keep looking for unfinished
                                //loading. If the only stillLoading is a
                                //plugin resource though, keep going,
                                //because it may be that a plugin resource
                                //is waiting on a non-plugin cycle.
                                return (needCycleCheck = false);
                            }
                        }
                    }
                });

                if (expired && noLoads.length) {
                    //If wait time expired, throw error of unloaded modules.
                    err = makeError('timeout', 'Load timeout for modules: ' + noLoads, null, noLoads);
                    err.contextName = context.contextName;
                    return onError(err);
                }

                //Not expired, check for a cycle.
                if (needCycleCheck) {
                    each(reqCalls, function (mod) {
                        breakCycle(mod, {}, {});
                    });
                }

                //If still waiting on loads, and the waiting load is something
                //other than a plugin resource, or there are still outstanding
                //scripts, then just try back later.
                if ((!expired || usingPathFallback) && stillLoading) {
                    //Something is still waiting to load. Wait for it, but only
                    //if a timeout is not already in effect.
                    if ((isBrowser || isWebWorker) && !checkLoadedTimeoutId) {
                        checkLoadedTimeoutId = setTimeout(function () {
                            checkLoadedTimeoutId = 0;
                            checkLoaded();
                        }, 50);
                    }
                }

                inCheckLoaded = false;
            }

            Module = function (map) {
                this.events = getOwn(undefEvents, map.id) || {};
                this.map = map;
                this.shim = getOwn(config.shim, map.id);
                this.depExports = [];
                this.depMaps = [];
                this.depMatched = [];
                this.pluginMaps = {};
                this.depCount = 0;

                /* this.exports this.factory
                 this.depMaps = [],
                 this.enabled, this.fetched
                 */
            };

            Module.prototype = {
                init: function (depMaps, factory, errback, options) {
                    options = options || {};

                    //Do not do more inits if already done. Can happen if there
                    //are multiple define calls for the same module. That is not
                    //a normal, common case, but it is also not unexpected.
                    if (this.inited) {
                        return;
                    }

                    this.factory = factory;

                    if (errback) {
                        //Register for errors on this module.
                        this.on('error', errback);
                    } else if (this.events.error) {
                        //If no errback already, but there are error listeners
                        //on this module, set up an errback to pass to the deps.
                        errback = bind(this, function (err) {
                            this.emit('error', err);
                        });
                    }

                    //Do a copy of the dependency array, so that
                    //source inputs are not modified. For example
                    //"shim" deps are passed in here directly, and
                    //doing a direct modification of the depMaps array
                    //would affect that config.
                    this.depMaps = depMaps && depMaps.slice(0);

                    this.errback = errback;

                    //Indicate this module has be initialized
                    this.inited = true;

                    this.ignore = options.ignore;

                    //Could have option to init this module in enabled mode,
                    //or could have been previously marked as enabled. However,
                    //the dependencies are not known until init is called. So
                    //if enabled previously, now trigger dependencies as enabled.
                    if (options.enabled || this.enabled) {
                        //Enable this module and dependencies.
                        //Will call this.check()
                        this.enable();
                    } else {
                        this.check();
                    }
                },

                defineDep: function (i, depExports) {
                    //Because of cycles, defined callback for a given
                    //export can be called more than once.
                    if (!this.depMatched[i]) {
                        this.depMatched[i] = true;
                        this.depCount -= 1;
                        this.depExports[i] = depExports;
                    }
                },

                fetch: function () {
                    if (this.fetched) {
                        return;
                    }
                    this.fetched = true;

                    context.startTime = (new Date()).getTime();

                    var map = this.map;

                    //If the manager is for a plugin managed resource,
                    //ask the plugin to load it now.
                    if (this.shim) {
                        context.makeRequire(this.map, {
                            enableBuildCallback: true
                        })(this.shim.deps || [], bind(this, function () {
                                return map.prefix ? this.callPlugin() : this.load();
                            }));
                    } else {
                        //Regular dependency.
                        return map.prefix ? this.callPlugin() : this.load();
                    }
                },

                load: function () {
                    var url = this.map.url;

                    //Regular dependency.
                    if (!urlFetched[url]) {
                        urlFetched[url] = true;
                        context.load(this.map.id, url);
                    }
                },

                /**
                 * Checks if the module is ready to define itself, and if so,
                 * define it.
                 */
                check: function () {
                    if (!this.enabled || this.enabling) {
                        return;
                    }

                    var err, cjsModule,
                        id = this.map.id,
                        depExports = this.depExports,
                        exports = this.exports,
                        factory = this.factory;

                    if (!this.inited) {
                        this.fetch();
                    } else if (this.error) {
                        this.emit('error', this.error);
                    } else if (!this.defining) {
                        //The factory could trigger another require call
                        //that would result in checking this module to
                        //define itself again. If already in the process
                        //of doing that, skip this work.
                        this.defining = true;

                        if (this.depCount < 1 && !this.defined) {
                            if (isFunction(factory)) {
                                //If there is an error listener, favor passing
                                //to that instead of throwing an error. However,
                                //only do it for define()'d  modules. require
                                //errbacks should not be called for failures in
                                //their callbacks (#699). However if a global
                                //onError is set, use that.
                                if ((this.events.error && this.map.isDefine) ||
                                    req.onError !== defaultOnError) {
                                    try {
                                        exports = context.execCb(id, factory, depExports, exports);
                                    } catch (e) {
                                        err = e;
                                    }
                                } else {
                                    exports = context.execCb(id, factory, depExports, exports);
                                }

                                // Favor return value over exports. If node/cjs in play,
                                // then will not have a return value anyway. Favor
                                // module.exports assignment over exports object.
                                if (this.map.isDefine && exports === undefined) {
                                    cjsModule = this.module;
                                    if (cjsModule) {
                                        exports = cjsModule.exports;
                                    } else if (this.usingExports) {
                                        //exports already set the defined value.
                                        exports = this.exports;
                                    }
                                }

                                if (err) {
                                    err.requireMap = this.map;
                                    err.requireModules = this.map.isDefine ? [this.map.id] : null;
                                    err.requireType = this.map.isDefine ? 'define' : 'require';
                                    return onError((this.error = err));
                                }

                            } else {
                                //Just a literal value
                                exports = factory;
                            }

                            this.exports = exports;

                            if (this.map.isDefine && !this.ignore) {
                                defined[id] = exports;

                                if (req.onResourceLoad) {
                                    req.onResourceLoad(context, this.map, this.depMaps);
                                }
                            }

                            //Clean up
                            cleanRegistry(id);

                            this.defined = true;
                        }

                        //Finished the define stage. Allow calling check again
                        //to allow define notifications below in the case of a
                        //cycle.
                        this.defining = false;

                        if (this.defined && !this.defineEmitted) {
                            this.defineEmitted = true;
                            this.emit('defined', this.exports);
                            this.defineEmitComplete = true;
                        }

                    }
                },

                callPlugin: function () {
                    var map = this.map,
                        id = map.id,
                    //Map already normalized the prefix.
                        pluginMap = makeModuleMap(map.prefix);

                    //Mark this as a dependency for this plugin, so it
                    //can be traced for cycles.
                    this.depMaps.push(pluginMap);

                    on(pluginMap, 'defined', bind(this, function (plugin) {
                        var load, normalizedMap, normalizedMod,
                            bundleId = getOwn(bundlesMap, this.map.id),
                            name = this.map.name,
                            parentName = this.map.parentMap ? this.map.parentMap.name : null,
                            localRequire = context.makeRequire(map.parentMap, {
                                enableBuildCallback: true
                            });

                        //If current map is not normalized, wait for that
                        //normalized name to load instead of continuing.
                        if (this.map.unnormalized) {
                            //Normalize the ID if the plugin allows it.
                            if (plugin.normalize) {
                                name = plugin.normalize(name, function (name) {
                                    return normalize(name, parentName, true);
                                }) || '';
                            }

                            //prefix and name should already be normalized, no need
                            //for applying map config again either.
                            normalizedMap = makeModuleMap(map.prefix + '!' + name,
                                this.map.parentMap);
                            on(normalizedMap,
                                'defined', bind(this, function (value) {
                                    this.init([], function () { return value; }, null, {
                                        enabled: true,
                                        ignore: true
                                    });
                                }));

                            normalizedMod = getOwn(registry, normalizedMap.id);
                            if (normalizedMod) {
                                //Mark this as a dependency for this plugin, so it
                                //can be traced for cycles.
                                this.depMaps.push(normalizedMap);

                                if (this.events.error) {
                                    normalizedMod.on('error', bind(this, function (err) {
                                        this.emit('error', err);
                                    }));
                                }
                                normalizedMod.enable();
                            }

                            return;
                        }

                        //If a paths config, then just load that file instead to
                        //resolve the plugin, as it is built into that paths layer.
                        if (bundleId) {
                            this.map.url = context.nameToUrl(bundleId);
                            this.load();
                            return;
                        }

                        load = bind(this, function (value) {
                            this.init([], function () { return value; }, null, {
                                enabled: true
                            });
                        });

                        load.error = bind(this, function (err) {
                            this.inited = true;
                            this.error = err;
                            err.requireModules = [id];

                            //Remove temp unnormalized modules for this module,
                            //since they will never be resolved otherwise now.
                            eachProp(registry, function (mod) {
                                if (mod.map.id.indexOf(id + '_unnormalized') === 0) {
                                    cleanRegistry(mod.map.id);
                                }
                            });

                            onError(err);
                        });

                        //Allow plugins to load other code without having to know the
                        //context or how to 'complete' the load.
                        load.fromText = bind(this, function (text, textAlt) {
                            /*jslint evil: true */
                            var moduleName = map.name,
                                moduleMap = makeModuleMap(moduleName),
                                hasInteractive = useInteractive;

                            //As of 2.1.0, support just passing the text, to reinforce
                            //fromText only being called once per resource. Still
                            //support old style of passing moduleName but discard
                            //that moduleName in favor of the internal ref.
                            if (textAlt) {
                                text = textAlt;
                            }

                            //Turn off interactive script matching for IE for any define
                            //calls in the text, then turn it back on at the end.
                            if (hasInteractive) {
                                useInteractive = false;
                            }

                            //Prime the system by creating a module instance for
                            //it.
                            getModule(moduleMap);

                            //Transfer any config to this other module.
                            if (hasProp(config.config, id)) {
                                config.config[moduleName] = config.config[id];
                            }

                            try {
                                req.exec(text);
                            } catch (e) {
                                return onError(makeError('fromtexteval',
                                    'fromText eval for ' + id +
                                        ' failed: ' + e,
                                    e,
                                    [id]));
                            }

                            if (hasInteractive) {
                                useInteractive = true;
                            }

                            //Mark this as a dependency for the plugin
                            //resource
                            this.depMaps.push(moduleMap);

                            //Support anonymous modules.
                            context.completeLoad(moduleName);

                            //Bind the value of that module to the value for this
                            //resource ID.
                            localRequire([moduleName], load);
                        });

                        //Use parentName here since the plugin's name is not reliable,
                        //could be some weird string with no path that actually wants to
                        //reference the parentName's path.
                        plugin.load(map.name, localRequire, load, config);
                    }));

                    context.enable(pluginMap, this);
                    this.pluginMaps[pluginMap.id] = pluginMap;
                },

                enable: function () {
                    enabledRegistry[this.map.id] = this;
                    this.enabled = true;

                    //Set flag mentioning that the module is enabling,
                    //so that immediate calls to the defined callbacks
                    //for dependencies do not trigger inadvertent load
                    //with the depCount still being zero.
                    this.enabling = true;

                    //Enable each dependency
                    each(this.depMaps, bind(this, function (depMap, i) {
                        var id, mod, handler;

                        if (typeof depMap === 'string') {
                            //Dependency needs to be converted to a depMap
                            //and wired up to this module.
                            depMap = makeModuleMap(depMap,
                                (this.map.isDefine ? this.map : this.map.parentMap),
                                false,
                                !this.skipMap);
                            this.depMaps[i] = depMap;

                            handler = getOwn(handlers, depMap.id);

                            if (handler) {
                                this.depExports[i] = handler(this);
                                return;
                            }

                            this.depCount += 1;

                            on(depMap, 'defined', bind(this, function (depExports) {
                                this.defineDep(i, depExports);
                                this.check();
                            }));

                            if (this.errback) {
                                on(depMap, 'error', bind(this, this.errback));
                            }
                        }

                        id = depMap.id;
                        mod = registry[id];

                        //Skip special modules like 'require', 'exports', 'module'
                        //Also, don't call enable if it is already enabled,
                        //important in circular dependency cases.
                        if (!hasProp(handlers, id) && mod && !mod.enabled) {
                            context.enable(depMap, this);
                        }
                    }));

                    //Enable each plugin that is used in
                    //a dependency
                    eachProp(this.pluginMaps, bind(this, function (pluginMap) {
                        var mod = getOwn(registry, pluginMap.id);
                        if (mod && !mod.enabled) {
                            context.enable(pluginMap, this);
                        }
                    }));

                    this.enabling = false;

                    this.check();
                },

                on: function (name, cb) {
                    var cbs = this.events[name];
                    if (!cbs) {
                        cbs = this.events[name] = [];
                    }
                    cbs.push(cb);
                },

                emit: function (name, evt) {
                    each(this.events[name], function (cb) {
                        cb(evt);
                    });
                    if (name === 'error') {
                        //Now that the error handler was triggered, remove
                        //the listeners, since this broken Module instance
                        //can stay around for a while in the registry.
                        delete this.events[name];
                    }
                }
            };

            function callGetModule(args) {
                //Skip modules already defined.
                if (!hasProp(defined, args[0])) {
                    getModule(makeModuleMap(args[0], null, true)).init(args[1], args[2]);
                }
            }

            function removeListener(node, func, name, ieName) {
                //Favor detachEvent because of IE9
                //issue, see attachEvent/addEventListener comment elsewhere
                //in this file.
                if (node.detachEvent && !isOpera) {
                    //Probably IE. If not it will throw an error, which will be
                    //useful to know.
                    if (ieName) {
                        node.detachEvent(ieName, func);
                    }
                } else {
                    node.removeEventListener(name, func, false);
                }
            }

            /**
             * Given an event from a script node, get the requirejs info from it,
             * and then removes the event listeners on the node.
             * @param {Event} evt
             * @returns {Object}
             */
            function getScriptData(evt) {
                //Using currentTarget instead of target for Firefox 2.0's sake. Not
                //all old browsers will be supported, but this one was easy enough
                //to support and still makes sense.
                var node = evt.currentTarget || evt.srcElement;

                //Remove the listeners once here.
                removeListener(node, context.onScriptLoad, 'load', 'onreadystatechange');
                removeListener(node, context.onScriptError, 'error');

                return {
                    node: node,
                    id: node && node.getAttribute('data-requiremodule')
                };
            }

            function intakeDefines() {
                var args;

                //Any defined modules in the global queue, intake them now.
                takeGlobalQueue();

                //Make sure any remaining defQueue items get properly processed.
                while (defQueue.length) {
                    args = defQueue.shift();
                    if (args[0] === null) {
                        return onError(makeError('mismatch', 'Mismatched anonymous define() module: ' + args[args.length - 1]));
                    } else {
                        //args are id, deps, factory. Should be normalized by the
                        //define() function.
                        callGetModule(args);
                    }
                }
            }

            context = {
                config: config,
                contextName: contextName,
                registry: registry,
                defined: defined,
                urlFetched: urlFetched,
                defQueue: defQueue,
                Module: Module,
                makeModuleMap: makeModuleMap,
                nextTick: req.nextTick,
                onError: onError,

                /**
                 * Set a configuration for the context.
                 * @param {Object} cfg config object to integrate.
                 */
                configure: function (cfg) {
                    //Make sure the baseUrl ends in a slash.
                    if (cfg.baseUrl) {
                        if (cfg.baseUrl.charAt(cfg.baseUrl.length - 1) !== '/') {
                            cfg.baseUrl += '/';
                        }
                    }

                    //Save off the paths since they require special processing,
                    //they are additive.
                    var shim = config.shim,
                        objs = {
                            paths: true,
                            bundles: true,
                            config: true,
                            map: true
                        };

                    eachProp(cfg, function (value, prop) {
                        if (objs[prop]) {
                            if (!config[prop]) {
                                config[prop] = {};
                            }
                            mixin(config[prop], value, true, true);
                        } else {
                            config[prop] = value;
                        }
                    });

                    //Reverse map the bundles
                    if (cfg.bundles) {
                        eachProp(cfg.bundles, function (value, prop) {
                            each(value, function (v) {
                                if (v !== prop) {
                                    bundlesMap[v] = prop;
                                }
                            });
                        });
                    }

                    //Merge shim
                    if (cfg.shim) {
                        eachProp(cfg.shim, function (value, id) {
                            //Normalize the structure
                            if (isArray(value)) {
                                value = {
                                    deps: value
                                };
                            }
                            if ((value.exports || value.init) && !value.exportsFn) {
                                value.exportsFn = context.makeShimExports(value);
                            }
                            shim[id] = value;
                        });
                        config.shim = shim;
                    }

                    //Adjust packages if necessary.
                    if (cfg.packages) {
                        each(cfg.packages, function (pkgObj) {
                            var location, name;

                            pkgObj = typeof pkgObj === 'string' ? { name: pkgObj } : pkgObj;

                            name = pkgObj.name;
                            location = pkgObj.location;
                            if (location) {
                                config.paths[name] = pkgObj.location;
                            }

                            //Save pointer to main module ID for pkg name.
                            //Remove leading dot in main, so main paths are normalized,
                            //and remove any trailing .js, since different package
                            //envs have different conventions: some use a module name,
                            //some use a file name.
                            config.pkgs[name] = pkgObj.name + '/' + (pkgObj.main || 'main')
                                .replace(currDirRegExp, '')
                                .replace(jsSuffixRegExp, '');
                        });
                    }

                    //If there are any "waiting to execute" modules in the registry,
                    //update the maps for them, since their info, like URLs to load,
                    //may have changed.
                    eachProp(registry, function (mod, id) {
                        //If module already has init called, since it is too
                        //late to modify them, and ignore unnormalized ones
                        //since they are transient.
                        if (!mod.inited && !mod.map.unnormalized) {
                            mod.map = makeModuleMap(id);
                        }
                    });

                    //If a deps array or a config callback is specified, then call
                    //require with those args. This is useful when require is defined as a
                    //config object before require.js is loaded.
                    if (cfg.deps || cfg.callback) {
                        context.require(cfg.deps || [], cfg.callback);
                    }
                },

                makeShimExports: function (value) {
                    function fn() {
                        var ret;
                        if (value.init) {
                            ret = value.init.apply(global, arguments);
                        }
                        return ret || (value.exports && getGlobal(value.exports));
                    }
                    return fn;
                },

                makeRequire: function (relMap, options) {
                    options = options || {};

                    function localRequire(deps, callback, errback) {
                        var id, map, requireMod;

                        if (options.enableBuildCallback && callback && isFunction(callback)) {
                            callback.__requireJsBuild = true;
                        }

                        if (typeof deps === 'string') {
                            if (isFunction(callback)) {
                                //Invalid call
                                return onError(makeError('requireargs', 'Invalid require call'), errback);
                            }

                            //If require|exports|module are requested, get the
                            //value for them from the special handlers. Caveat:
                            //this only works while module is being defined.
                            if (relMap && hasProp(handlers, deps)) {
                                return handlers[deps](registry[relMap.id]);
                            }

                            //Synchronous access to one module. If require.get is
                            //available (as in the Node adapter), prefer that.
                            if (req.get) {
                                return req.get(context, deps, relMap, localRequire);
                            }

                            //Normalize module name, if it contains . or ..
                            map = makeModuleMap(deps, relMap, false, true);
                            id = map.id;

                            if (!hasProp(defined, id)) {
                                return onError(makeError('notloaded', 'Module name "' +
                                    id +
                                    '" has not been loaded yet for context: ' +
                                    contextName +
                                    (relMap ? '' : '. Use require([])')));
                            }
                            return defined[id];
                        }

                        //Grab defines waiting in the global queue.
                        intakeDefines();

                        //Mark all the dependencies as needing to be loaded.
                        context.nextTick(function () {
                            //Some defines could have been added since the
                            //require call, collect them.
                            intakeDefines();

                            requireMod = getModule(makeModuleMap(null, relMap));

                            //Store if map config should be applied to this require
                            //call for dependencies.
                            requireMod.skipMap = options.skipMap;

                            requireMod.init(deps, callback, errback, {
                                enabled: true
                            });

                            checkLoaded();
                        });

                        return localRequire;
                    }

                    mixin(localRequire, {
                        isBrowser: isBrowser,

                        /**
                         * Converts a module name + .extension into an URL path.
                         * *Requires* the use of a module name. It does not support using
                         * plain URLs like nameToUrl.
                         */
                        toUrl: function (moduleNamePlusExt) {
                            var ext,
                                index = moduleNamePlusExt.lastIndexOf('.'),
                                segment = moduleNamePlusExt.split('/')[0],
                                isRelative = segment === '.' || segment === '..';

                            //Have a file extension alias, and it is not the
                            //dots from a relative path.
                            if (index !== -1 && (!isRelative || index > 1)) {
                                ext = moduleNamePlusExt.substring(index, moduleNamePlusExt.length);
                                moduleNamePlusExt = moduleNamePlusExt.substring(0, index);
                            }

                            return context.nameToUrl(normalize(moduleNamePlusExt,
                                relMap && relMap.id, true), ext,  true);
                        },

                        defined: function (id) {
                            return hasProp(defined, makeModuleMap(id, relMap, false, true).id);
                        },

                        specified: function (id) {
                            id = makeModuleMap(id, relMap, false, true).id;
                            return hasProp(defined, id) || hasProp(registry, id);
                        }
                    });

                    //Only allow undef on top level require calls
                    if (!relMap) {
                        localRequire.undef = function (id) {
                            //Bind any waiting define() calls to this context,
                            //fix for #408
                            takeGlobalQueue();

                            var map = makeModuleMap(id, relMap, true),
                                mod = getOwn(registry, id);

                            removeScript(id);

                            delete defined[id];
                            delete urlFetched[map.url];
                            delete undefEvents[id];

                            //Clean queued defines too. Go backwards
                            //in array so that the splices do not
                            //mess up the iteration.
                            eachReverse(defQueue, function(args, i) {
                                if(args[0] === id) {
                                    defQueue.splice(i, 1);
                                }
                            });

                            if (mod) {
                                //Hold on to listeners in case the
                                //module will be attempted to be reloaded
                                //using a different config.
                                if (mod.events.defined) {
                                    undefEvents[id] = mod.events;
                                }

                                cleanRegistry(id);
                            }
                        };
                    }

                    return localRequire;
                },

                /**
                 * Called to enable a module if it is still in the registry
                 * awaiting enablement. A second arg, parent, the parent module,
                 * is passed in for context, when this method is overridden by
                 * the optimizer. Not shown here to keep code compact.
                 */
                enable: function (depMap) {
                    var mod = getOwn(registry, depMap.id);
                    if (mod) {
                        getModule(depMap).enable();
                    }
                },

                /**
                 * Internal method used by environment adapters to complete a load event.
                 * A load event could be a script load or just a load pass from a synchronous
                 * load call.
                 * @param {String} moduleName the name of the module to potentially complete.
                 */
                completeLoad: function (moduleName) {
                    var found, args, mod,
                        shim = getOwn(config.shim, moduleName) || {},
                        shExports = shim.exports;

                    takeGlobalQueue();

                    while (defQueue.length) {
                        args = defQueue.shift();
                        if (args[0] === null) {
                            args[0] = moduleName;
                            //If already found an anonymous module and bound it
                            //to this name, then this is some other anon module
                            //waiting for its completeLoad to fire.
                            if (found) {
                                break;
                            }
                            found = true;
                        } else if (args[0] === moduleName) {
                            //Found matching define call for this script!
                            found = true;
                        }

                        callGetModule(args);
                    }

                    //Do this after the cycle of callGetModule in case the result
                    //of those calls/init calls changes the registry.
                    mod = getOwn(registry, moduleName);

                    if (!found && !hasProp(defined, moduleName) && mod && !mod.inited) {
                        if (config.enforceDefine && (!shExports || !getGlobal(shExports))) {
                            if (hasPathFallback(moduleName)) {
                                return;
                            } else {
                                return onError(makeError('nodefine',
                                    'No define call for ' + moduleName,
                                    null,
                                    [moduleName]));
                            }
                        } else {
                            //A script that does not call define(), so just simulate
                            //the call for it.
                            callGetModule([moduleName, (shim.deps || []), shim.exportsFn]);
                        }
                    }

                    checkLoaded();
                },

                /**
                 * Converts a module name to a file path. Supports cases where
                 * moduleName may actually be just an URL.
                 * Note that it **does not** call normalize on the moduleName,
                 * it is assumed to have already been normalized. This is an
                 * internal API, not a public one. Use toUrl for the public API.
                 */
                nameToUrl: function (moduleName, ext, skipExt) {
                    var paths, syms, i, parentModule, url,
                        parentPath, bundleId,
                        pkgMain = getOwn(config.pkgs, moduleName);

                    if (pkgMain) {
                        moduleName = pkgMain;
                    }

                    bundleId = getOwn(bundlesMap, moduleName);

                    if (bundleId) {
                        return context.nameToUrl(bundleId, ext, skipExt);
                    }

                    //If a colon is in the URL, it indicates a protocol is used and it is just
                    //an URL to a file, or if it starts with a slash, contains a query arg (i.e. ?)
                    //or ends with .js, then assume the user meant to use an url and not a module id.
                    //The slash is important for protocol-less URLs as well as full paths.
                    if (req.jsExtRegExp.test(moduleName)) {
                        //Just a plain path, not module name lookup, so just return it.
                        //Add extension if it is included. This is a bit wonky, only non-.js things pass
                        //an extension, this method probably needs to be reworked.
                        url = moduleName + (ext || '');
                    } else {
                        //A module that needs to be converted to a path.
                        paths = config.paths;

                        syms = moduleName.split('/');
                        //For each module name segment, see if there is a path
                        //registered for it. Start with most specific name
                        //and work up from it.
                        for (i = syms.length; i > 0; i -= 1) {
                            parentModule = syms.slice(0, i).join('/');

                            parentPath = getOwn(paths, parentModule);
                            if (parentPath) {
                                //If an array, it means there are a few choices,
                                //Choose the one that is desired
                                if (isArray(parentPath)) {
                                    parentPath = parentPath[0];
                                }
                                syms.splice(0, i, parentPath);
                                break;
                            }
                        }

                        //Join the path parts together, then figure out if baseUrl is needed.
                        url = syms.join('/');
                        url += (ext || (/^data\:|\?/.test(url) || skipExt ? '' : '.js'));
                        url = (url.charAt(0) === '/' || url.match(/^[\w\+\.\-]+:/) ? '' : config.baseUrl) + url;
                    }

                    return config.urlArgs ? url +
                        ((url.indexOf('?') === -1 ? '?' : '&') +
                            config.urlArgs) : url;
                },

                //Delegates to req.load. Broken out as a separate function to
                //allow overriding in the optimizer.
                load: function (id, url) {
                    req.load(context, id, url);
                },

                /**
                 * Executes a module callback function. Broken out as a separate function
                 * solely to allow the build system to sequence the files in the built
                 * layer in the right sequence.
                 *
                 * @private
                 */
                execCb: function (name, callback, args, exports) {
                    return callback.apply(exports, args);
                },

                /**
                 * callback for script loads, used to check status of loading.
                 *
                 * @param {Event} evt the event from the browser for the script
                 * that was loaded.
                 */
                onScriptLoad: function (evt) {
                    //Using currentTarget instead of target for Firefox 2.0's sake. Not
                    //all old browsers will be supported, but this one was easy enough
                    //to support and still makes sense.
                    if (evt.type === 'load' ||
                        (readyRegExp.test((evt.currentTarget || evt.srcElement).readyState))) {
                        //Reset interactive script so a script node is not held onto for
                        //to long.
                        interactiveScript = null;

                        //Pull out the name of the module and the context.
                        var data = getScriptData(evt);
                        context.completeLoad(data.id);
                    }
                },

                /**
                 * Callback for script errors.
                 */
                onScriptError: function (evt) {
                    var data = getScriptData(evt);
                    if (!hasPathFallback(data.id)) {
                        return onError(makeError('scripterror', 'Script error for: ' + data.id, evt, [data.id]));
                    }
                }
            };

            context.require = context.makeRequire();
            return context;
        }

        /**
         * Main entry point.
         *
         * If the only argument to require is a string, then the module that
         * is represented by that string is fetched for the appropriate context.
         *
         * If the first argument is an array, then it will be treated as an array
         * of dependency string names to fetch. An optional function callback can
         * be specified to execute when all of those dependencies are available.
         *
         * Make a local req variable to help Caja compliance (it assumes things
         * on a require that are not standardized), and to give a short
         * name for minification/local scope use.
         */
        req = requirejs = function (deps, callback, errback, optional) {

            //Find the right context, use default
            var context, config,
                contextName = defContextName;

            // Determine if have config object in the call.
            if (!isArray(deps) && typeof deps !== 'string') {
                // deps is a config object
                config = deps;
                if (isArray(callback)) {
                    // Adjust args if there are dependencies
                    deps = callback;
                    callback = errback;
                    errback = optional;
                } else {
                    deps = [];
                }
            }

            if (config && config.context) {
                contextName = config.context;
            }

            context = getOwn(contexts, contextName);
            if (!context) {
                context = contexts[contextName] = req.s.newContext(contextName);
            }

            if (config) {
                context.configure(config);
            }

            return context.require(deps, callback, errback);
        };

        /**
         * Support require.config() to make it easier to cooperate with other
         * AMD loaders on globally agreed names.
         */
        req.config = function (config) {
            return req(config);
        };

        /**
         * Execute something after the current tick
         * of the event loop. Override for other envs
         * that have a better solution than setTimeout.
         * @param  {Function} fn function to execute later.
         */
        req.nextTick = typeof setTimeout !== 'undefined' ? function (fn) {
            setTimeout(fn, 4);
        } : function (fn) { fn(); };

        /**
         * Export require as a global, but only if it does not already exist.
         */
        if (!require) {
            require = req;
        }

        req.version = version;

        //Used to filter out dependencies that are already paths.
        req.jsExtRegExp = /^\/|:|\?|\.js$/;
        req.isBrowser = isBrowser;
        s = req.s = {
            contexts: contexts,
            newContext: newContext
        };

        //Create default context.
        req({});

        //Exports some context-sensitive methods on global require.
        each([
            'toUrl',
            'undef',
            'defined',
            'specified'
        ], function (prop) {
            //Reference from contexts instead of early binding to default context,
            //so that during builds, the latest instance of the default context
            //with its config gets used.
            req[prop] = function () {
                var ctx = contexts[defContextName];
                return ctx.require[prop].apply(ctx, arguments);
            };
        });

        if (isBrowser) {
            head = s.head = document.getElementsByTagName('head')[0];
            //If BASE tag is in play, using appendChild is a problem for IE6.
            //When that browser dies, this can be removed. Details in this jQuery bug:
            //http://dev.jquery.com/ticket/2709
            baseElement = document.getElementsByTagName('base')[0];
            if (baseElement) {
                head = s.head = baseElement.parentNode;
            }
        }

        /**
         * Any errors that require explicitly generates will be passed to this
         * function. Intercept/override it if you want custom error handling.
         * @param {Error} err the error object.
         */
        req.onError = defaultOnError;

        /**
         * Creates the node for the load command. Only used in browser envs.
         */
        req.createNode = function (config, moduleName, url) {
            var node = config.xhtml ?
                document.createElementNS('http://www.w3.org/1999/xhtml', 'html:script') :
                document.createElement('script');
            node.type = config.scriptType || 'text/javascript';
            node.charset = 'utf-8';
            node.async = true;
            return node;
        };

        /**
         * Does the request to load a module for the browser case.
         * Make this a separate function to allow other environments
         * to override it.
         *
         * @param {Object} context the require context to find state.
         * @param {String} moduleName the name of the module.
         * @param {Object} url the URL to the module.
         */
        req.load = function (context, moduleName, url) {
            var config = (context && context.config) || {},
                node;
            if (isBrowser) {
                //In the browser so use a script tag
                node = req.createNode(config, moduleName, url);

                node.setAttribute('data-requirecontext', context.contextName);
                node.setAttribute('data-requiremodule', moduleName);

                //Set up load listener. Test attachEvent first because IE9 has
                //a subtle issue in its addEventListener and script onload firings
                //that do not match the behavior of all other browsers with
                //addEventListener support, which fire the onload event for a
                //script right after the script execution. See:
                //https://connect.microsoft.com/IE/feedback/details/648057/script-onload-event-is-not-fired-immediately-after-script-execution
                //UNFORTUNATELY Opera implements attachEvent but does not follow the script
                //script execution mode.
                if (node.attachEvent &&
                    //Check if node.attachEvent is artificially added by custom script or
                    //natively supported by browser
                    //read https://github.com/jrburke/requirejs/issues/187
                    //if we can NOT find [native code] then it must NOT natively supported.
                    //in IE8, node.attachEvent does not have toString()
                    //Note the test for "[native code" with no closing brace, see:
                    //https://github.com/jrburke/requirejs/issues/273
                    !(node.attachEvent.toString && node.attachEvent.toString().indexOf('[native code') < 0) &&
                    !isOpera) {
                    //Probably IE. IE (at least 6-8) do not fire
                    //script onload right after executing the script, so
                    //we cannot tie the anonymous define call to a name.
                    //However, IE reports the script as being in 'interactive'
                    //readyState at the time of the define call.
                    useInteractive = true;

                    node.attachEvent('onreadystatechange', context.onScriptLoad);
                    //It would be great to add an error handler here to catch
                    //404s in IE9+. However, onreadystatechange will fire before
                    //the error handler, so that does not help. If addEventListener
                    //is used, then IE will fire error before load, but we cannot
                    //use that pathway given the connect.microsoft.com issue
                    //mentioned above about not doing the 'script execute,
                    //then fire the script load event listener before execute
                    //next script' that other browsers do.
                    //Best hope: IE10 fixes the issues,
                    //and then destroys all installs of IE 6-9.
                    //node.attachEvent('onerror', context.onScriptError);
                } else {
                    node.addEventListener('load', context.onScriptLoad, false);
                    node.addEventListener('error', context.onScriptError, false);
                }
                node.src = url;

                //For some cache cases in IE 6-8, the script executes before the end
                //of the appendChild execution, so to tie an anonymous define
                //call to the module name (which is stored on the node), hold on
                //to a reference to this node, but clear after the DOM insertion.
                currentlyAddingScript = node;
                if (baseElement) {
                    head.insertBefore(node, baseElement);
                } else {
                    head.appendChild(node);
                }
                currentlyAddingScript = null;

                return node;
            } else if (isWebWorker) {
                try {
                    //In a web worker, use importScripts. This is not a very
                    //efficient use of importScripts, importScripts will block until
                    //its script is downloaded and evaluated. However, if web workers
                    //are in play, the expectation that a build has been done so that
                    //only one script needs to be loaded anyway. This may need to be
                    //reevaluated if other use cases become common.
                    importScripts(url);

                    //Account for anonymous modules
                    context.completeLoad(moduleName);
                } catch (e) {
                    context.onError(makeError('importscripts',
                        'importScripts failed for ' +
                            moduleName + ' at ' + url,
                        e,
                        [moduleName]));
                }
            }
        };

        function getInteractiveScript() {
            if (interactiveScript && interactiveScript.readyState === 'interactive') {
                return interactiveScript;
            }

            eachReverse(scripts(), function (script) {
                if (script.readyState === 'interactive') {
                    return (interactiveScript = script);
                }
            });
            return interactiveScript;
        }

        //Look for a data-main script attribute, which could also adjust the baseUrl.
        if (isBrowser && !cfg.skipDataMain) {
            //Figure out baseUrl. Get it from the script tag with require.js in it.
            eachReverse(scripts(), function (script) {
                //Set the 'head' where we can append children by
                //using the script's parent.
                if (!head) {
                    head = script.parentNode;
                }

                //Look for a data-main attribute to set main script for the page
                //to load. If it is there, the path to data main becomes the
                //baseUrl, if it is not already set.
                dataMain = script.getAttribute('data-main');
                if (dataMain) {
                    //Preserve dataMain in case it is a path (i.e. contains '?')
                    mainScript = dataMain;

                    //Set final baseUrl if there is not already an explicit one.
                    if (!cfg.baseUrl) {
                        //Pull off the directory of data-main for use as the
                        //baseUrl.
                        src = mainScript.split('/');
                        mainScript = src.pop();
                        subPath = src.length ? src.join('/')  + '/' : './';

                        cfg.baseUrl = subPath;
                    }

                    //Strip off any trailing .js since mainScript is now
                    //like a module name.
                    mainScript = mainScript.replace(jsSuffixRegExp, '');

                    //If mainScript is still a path, fall back to dataMain
                    if (req.jsExtRegExp.test(mainScript)) {
                        mainScript = dataMain;
                    }

                    //Put the data-main script in the files to load.
                    cfg.deps = cfg.deps ? cfg.deps.concat(mainScript) : [mainScript];

                    return true;
                }
            });
        }

        /**
         * The function that handles definitions of modules. Differs from
         * require() in that a string for the module should be the first argument,
         * and the function to execute after dependencies are loaded should
         * return a value to define the module corresponding to the first argument's
         * name.
         */
        define = function (name, deps, callback) {
            var node, context;

            //Allow for anonymous modules
            if (typeof name !== 'string') {
                //Adjust args appropriately
                callback = deps;
                deps = name;
                name = null;
            }

            //This module may not have dependencies
            if (!isArray(deps)) {
                callback = deps;
                deps = null;
            }

            //If no name, and callback is a function, then figure out if it a
            //CommonJS thing with dependencies.
            if (!deps && isFunction(callback)) {
                deps = [];
                //Remove comments from the callback string,
                //look for require calls, and pull them into the dependencies,
                //but only if there are function args.
                if (callback.length) {
                    callback
                        .toString()
                        .replace(commentRegExp, '')
                        .replace(cjsRequireRegExp, function (match, dep) {
                            deps.push(dep);
                        });

                    //May be a CommonJS thing even without require calls, but still
                    //could use exports, and module. Avoid doing exports and module
                    //work though if it just needs require.
                    //REQUIRES the function to expect the CommonJS variables in the
                    //order listed below.
                    deps = (callback.length === 1 ? ['require'] : ['require', 'exports', 'module']).concat(deps);
                }
            }

            //If in IE 6-8 and hit an anonymous define() call, do the interactive
            //work.
            if (useInteractive) {
                node = currentlyAddingScript || getInteractiveScript();
                if (node) {
                    if (!name) {
                        name = node.getAttribute('data-requiremodule');
                    }
                    context = contexts[node.getAttribute('data-requirecontext')];
                }
            }

            //Always save off evaluating the def call until the script onload handler.
            //This allows multiple modules to be in a file without prematurely
            //tracing dependencies, and allows for anonymous module support,
            //where the module name is not known until the script onload event
            //occurs. If no context, use the global queue, and get it processed
            //in the onscript load callback.
            (context ? context.defQueue : globalDefQueue).push([name, deps, callback]);
        };

        define.amd = {
            jQuery: true
        };


        /**
         * Executes the text. Normally just uses eval, but can be modified
         * to use a better, environment-specific call. Only used for transpiling
         * loader plugins, not for plain JS modules.
         * @param {String} text the text to execute/evaluate.
         */
        req.exec = function (text) {
            /*jslint evil: true */
            return eval(text);
        };

        //Set up with config info.
        req(cfg);
    }(this));



    this.requirejsVars = {
        require: require,
        requirejs: require,
        define: define
    };

    if (env === 'browser') {
        /**
         * @license RequireJS rhino Copyright (c) 2012-2014, The Dojo Foundation All Rights Reserved.
         * Available via the MIT or new BSD license.
         * see: http://github.com/jrburke/requirejs for details
         */

//sloppy since eval enclosed with use strict causes problems if the source
//text is not strict-compliant.
        /*jslint sloppy: true, evil: true */
        /*global require, XMLHttpRequest */

        (function () {
            require.load = function (context, moduleName, url) {
                var xhr = new XMLHttpRequest();

                xhr.open('GET', url, true);
                xhr.send();

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4) {
                        eval(xhr.responseText);

                        //Support anonymous modules.
                        context.completeLoad(moduleName);
                    }
                };
            };
        }());
    } else if (env === 'rhino') {
        /**
         * @license RequireJS rhino Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
         * Available via the MIT or new BSD license.
         * see: http://github.com/jrburke/requirejs for details
         */

        /*jslint */
        /*global require: false, java: false, load: false */

        (function () {
            'use strict';
            require.load = function (context, moduleName, url) {

                load(url);

                //Support anonymous modules.
                context.completeLoad(moduleName);
            };

        }());
    } else if (env === 'node') {
        this.requirejsVars.nodeRequire = nodeRequire;
        require.nodeRequire = nodeRequire;

        /**
         * @license RequireJS node Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
         * Available via the MIT or new BSD license.
         * see: http://github.com/jrburke/requirejs for details
         */

        /*jslint regexp: false */
        /*global require: false, define: false, requirejsVars: false, process: false */

        /**
         * This adapter assumes that x.js has loaded it and set up
         * some variables. This adapter just allows limited RequireJS
         * usage from within the requirejs directory. The general
         * node adapater is r.js.
         */

        (function () {
            'use strict';

            var nodeReq = requirejsVars.nodeRequire,
                req = requirejsVars.require,
                def = requirejsVars.define,
                fs = nodeReq('fs'),
                path = nodeReq('path'),
                vm = nodeReq('vm'),
            //In Node 0.7+ existsSync is on fs.
                exists = fs.existsSync || path.existsSync,
                hasOwn = Object.prototype.hasOwnProperty;

            function hasProp(obj, prop) {
                return hasOwn.call(obj, prop);
            }

            function syncTick(fn) {
                fn();
            }

            function makeError(message, moduleName) {
                var err = new Error(message);
                err.requireModules = [moduleName];
                return err;
            }

            //Supply an implementation that allows synchronous get of a module.
            req.get = function (context, moduleName, relModuleMap, localRequire) {
                if (moduleName === "require" || moduleName === "exports" || moduleName === "module") {
                    context.onError(makeError("Explicit require of " + moduleName + " is not allowed.", moduleName));
                }

                var ret, oldTick,
                    moduleMap = context.makeModuleMap(moduleName, relModuleMap, false, true);

                //Normalize module name, if it contains . or ..
                moduleName = moduleMap.id;

                if (hasProp(context.defined, moduleName)) {
                    ret = context.defined[moduleName];
                } else {
                    if (ret === undefined) {
                        //Make sure nextTick for this type of call is sync-based.
                        oldTick = context.nextTick;
                        context.nextTick = syncTick;
                        try {
                            if (moduleMap.prefix) {
                                //A plugin, call requirejs to handle it. Now that
                                //nextTick is syncTick, the require will complete
                                //synchronously.
                                localRequire([moduleMap.originalName]);

                                //Now that plugin is loaded, can regenerate the moduleMap
                                //to get the final, normalized ID.
                                moduleMap = context.makeModuleMap(moduleMap.originalName, relModuleMap, false, true);
                                moduleName = moduleMap.id;
                            } else {
                                //Try to dynamically fetch it.
                                req.load(context, moduleName, moduleMap.url);

                                //Enable the module
                                context.enable(moduleMap, relModuleMap);
                            }

                            //Break any cycles by requiring it normally, but this will
                            //finish synchronously
                            context.require([moduleName]);

                            //The above calls are sync, so can do the next thing safely.
                            ret = context.defined[moduleName];
                        } finally {
                            context.nextTick = oldTick;
                        }
                    }
                }

                return ret;
            };

            req.nextTick = function (fn) {
                process.nextTick(fn);
            };

            //Add wrapper around the code so that it gets the requirejs
            //API instead of the Node API, and it is done lexically so
            //that it survives later execution.
            req.makeNodeWrapper = function (contents) {
                return '(function (require, requirejs, define) { ' +
                    contents +
                    '\n}(requirejsVars.require, requirejsVars.requirejs, requirejsVars.define));';
            };

            req.load = function (context, moduleName, url) {
                var contents, err,
                    config = context.config;

                if (config.shim[moduleName] && (!config.suppress || !config.suppress.nodeShim)) {
                    console.warn('Shim config not supported in Node, may or may not work. Detected ' +
                        'for module: ' + moduleName);
                }

                if (exists(url)) {
                    contents = fs.readFileSync(url, 'utf8');

                    contents = req.makeNodeWrapper(contents);
                    try {
                        vm.runInThisContext(contents, fs.realpathSync(url));
                    } catch (e) {
                        err = new Error('Evaluating ' + url + ' as module "' +
                            moduleName + '" failed with error: ' + e);
                        err.originalError = e;
                        err.moduleName = moduleName;
                        err.requireModules = [moduleName];
                        err.fileName = url;
                        return context.onError(err);
                    }
                } else {
                    def(moduleName, function () {
                        //Get the original name, since relative requires may be
                        //resolved differently in node (issue #202). Also, if relative,
                        //make it relative to the URL of the item requesting it
                        //(issue #393)
                        var dirName,
                            map = hasProp(context.registry, moduleName) &&
                                context.registry[moduleName].map,
                            parentMap = map && map.parentMap,
                            originalName = map && map.originalName;

                        if (originalName.charAt(0) === '.' && parentMap) {
                            dirName = parentMap.url.split('/');
                            dirName.pop();
                            originalName = dirName.join('/') + '/' + originalName;
                        }

                        try {
                            return (context.config.nodeRequire || req.nodeRequire)(originalName);
                        } catch (e) {
                            err = new Error('Tried loading "' + moduleName + '" at ' +
                                url + ' then tried node\'s require("' +
                                originalName + '") and it failed ' +
                                'with error: ' + e);
                            err.originalError = e;
                            err.moduleName = originalName;
                            err.requireModules = [moduleName];
                            throw err;
                        }
                    });
                }

                //Support anonymous modules.
                context.completeLoad(moduleName);
            };

            //Override to provide the function wrapper for define/require.
            req.exec = function (text) {
                /*jslint evil: true */
                text = req.makeNodeWrapper(text);
                return eval(text);
            };
        }());

    } else if (env === 'xpconnect') {
        /**
         * @license RequireJS xpconnect Copyright (c) 2013-2014, The Dojo Foundation All Rights Reserved.
         * Available via the MIT or new BSD license.
         * see: http://github.com/jrburke/requirejs for details
         */

        /*jslint */
        /*global require, load */

        (function () {
            'use strict';
            require.load = function (context, moduleName, url) {

                load(url);

                //Support anonymous modules.
                context.completeLoad(moduleName);
            };

        }());

    }

    //Support a default file name to execute. Useful for hosted envs
    //like Joyent where it defaults to a server.js as the only executed
    //script. But only do it if this is not an optimization run.
    if (commandOption !== 'o' && (!fileName || !jsSuffixRegExp.test(fileName))) {
        fileName = 'main.js';
    }

    /**
     * Loads the library files that can be used for the optimizer, or for other
     * tasks.
     */
    function loadLib() {
        /**
         * @license Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
         * Available via the MIT or new BSD license.
         * see: http://github.com/jrburke/requirejs for details
         */

        /*jslint strict: false */
        /*global Packages: false, process: false, window: false, navigator: false,
         document: false, define: false */

        /**
         * A plugin that modifies any /env/ path to be the right path based on
         * the host environment. Right now only works for Node, Rhino and browser.
         */
        (function () {
            var pathRegExp = /(\/|^)env\/|\{env\}/,
                env = 'unknown';

            if (typeof process !== 'undefined' && process.versions && !!process.versions.node) {
                env = 'node';
            } else if (typeof Packages !== 'undefined') {
                env = 'rhino';
            } else if ((typeof navigator !== 'undefined' && typeof document !== 'undefined') ||
                (typeof importScripts !== 'undefined' && typeof self !== 'undefined')) {
                env = 'browser';
            } else if (typeof Components !== 'undefined' && Components.classes && Components.interfaces) {
                env = 'xpconnect';
            }

            define('env', {
                get: function () {
                    return env;
                },

                load: function (name, req, load, config) {
                    //Allow override in the config.
                    if (config.env) {
                        env = config.env;
                    }

                    name = name.replace(pathRegExp, function (match, prefix) {
                        if (match.indexOf('{') === -1) {
                            return prefix + env + '/';
                        } else {
                            return env;
                        }
                    });

                    req([name], function (mod) {
                        load(mod);
                    });
                }
            });
        }());
        /**
         * @license Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
         * Available via the MIT or new BSD license.
         * see: http://github.com/jrburke/requirejs for details
         */

        /*jslint plusplus: true */
        /*global define, java */

        define('lang', function () {
            'use strict';

            var lang, isJavaObj,
                hasOwn = Object.prototype.hasOwnProperty;

            function hasProp(obj, prop) {
                return hasOwn.call(obj, prop);
            }

            isJavaObj = function () {
                return false;
            };

            if (typeof java !== 'undefined' && java.lang && java.lang.Object) {
                isJavaObj = function (obj) {
                    return obj instanceof java.lang.Object;
                };
            }

            lang = {
                backSlashRegExp: /\\/g,
                ostring: Object.prototype.toString,

                isArray: Array.isArray || function (it) {
                    return lang.ostring.call(it) === "[object Array]";
                },

                isFunction: function(it) {
                    return lang.ostring.call(it) === "[object Function]";
                },

                isRegExp: function(it) {
                    return it && it instanceof RegExp;
                },

                hasProp: hasProp,

                //returns true if the object does not have an own property prop,
                //or if it does, it is a falsy value.
                falseProp: function (obj, prop) {
                    return !hasProp(obj, prop) || !obj[prop];
                },

                //gets own property value for given prop on object
                getOwn: function (obj, prop) {
                    return hasProp(obj, prop) && obj[prop];
                },

                _mixin: function(dest, source, override){
                    var name;
                    for (name in source) {
                        if(source.hasOwnProperty(name) &&
                            (override || !dest.hasOwnProperty(name))) {
                            dest[name] = source[name];
                        }
                    }

                    return dest; // Object
                },

                /**
                 * mixin({}, obj1, obj2) is allowed. If the last argument is a boolean,
                 * then the source objects properties are force copied over to dest.
                 */
                mixin: function(dest){
                    var parameters = Array.prototype.slice.call(arguments),
                        override, i, l;

                    if (!dest) { dest = {}; }

                    if (parameters.length > 2 && typeof arguments[parameters.length-1] === 'boolean') {
                        override = parameters.pop();
                    }

                    for (i = 1, l = parameters.length; i < l; i++) {
                        lang._mixin(dest, parameters[i], override);
                    }
                    return dest; // Object
                },

                /**
                 * Does a deep mix of source into dest, where source values override
                 * dest values if a winner is needed.
                 * @param  {Object} dest destination object that receives the mixed
                 * values.
                 * @param  {Object} source source object contributing properties to mix
                 * in.
                 * @return {[Object]} returns dest object with the modification.
                 */
                deepMix: function(dest, source) {
                    lang.eachProp(source, function (value, prop) {
                        if (typeof value === 'object' && value &&
                            !lang.isArray(value) && !lang.isFunction(value) &&
                            !(value instanceof RegExp)) {

                            if (!dest[prop]) {
                                dest[prop] = {};
                            }
                            lang.deepMix(dest[prop], value);
                        } else {
                            dest[prop] = value;
                        }
                    });
                    return dest;
                },

                /**
                 * Does a type of deep copy. Do not give it anything fancy, best
                 * for basic object copies of objects that also work well as
                 * JSON-serialized things, or has properties pointing to functions.
                 * For non-array/object values, just returns the same object.
                 * @param  {Object} obj      copy properties from this object
                 * @param  {Object} [result] optional result object to use
                 * @return {Object}
                 */
                deeplikeCopy: function (obj) {
                    var type, result;

                    if (lang.isArray(obj)) {
                        result = [];
                        obj.forEach(function(value) {
                            result.push(lang.deeplikeCopy(value));
                        });
                        return result;
                    }

                    type = typeof obj;
                    if (obj === null || obj === undefined || type === 'boolean' ||
                        type === 'string' || type === 'number' || lang.isFunction(obj) ||
                        lang.isRegExp(obj)|| isJavaObj(obj)) {
                        return obj;
                    }

                    //Anything else is an object, hopefully.
                    result = {};
                    lang.eachProp(obj, function(value, key) {
                        result[key] = lang.deeplikeCopy(value);
                    });
                    return result;
                },

                delegate: (function () {
                    // boodman/crockford delegation w/ cornford optimization
                    function TMP() {}
                    return function (obj, props) {
                        TMP.prototype = obj;
                        var tmp = new TMP();
                        TMP.prototype = null;
                        if (props) {
                            lang.mixin(tmp, props);
                        }
                        return tmp; // Object
                    };
                }()),

                /**
                 * Helper function for iterating over an array. If the func returns
                 * a true value, it will break out of the loop.
                 */
                each: function each(ary, func) {
                    if (ary) {
                        var i;
                        for (i = 0; i < ary.length; i += 1) {
                            if (func(ary[i], i, ary)) {
                                break;
                            }
                        }
                    }
                },

                /**
                 * Cycles over properties in an object and calls a function for each
                 * property value. If the function returns a truthy value, then the
                 * iteration is stopped.
                 */
                eachProp: function eachProp(obj, func) {
                    var prop;
                    for (prop in obj) {
                        if (hasProp(obj, prop)) {
                            if (func(obj[prop], prop)) {
                                break;
                            }
                        }
                    }
                },

                //Similar to Function.prototype.bind, but the "this" object is specified
                //first, since it is easier to read/figure out what "this" will be.
                bind: function bind(obj, fn) {
                    return function () {
                        return fn.apply(obj, arguments);
                    };
                },

                //Escapes a content string to be be a string that has characters escaped
                //for inclusion as part of a JS string.
                jsEscape: function (content) {
                    return content.replace(/(["'\\])/g, '\\$1')
                        .replace(/[\f]/g, "\\f")
                        .replace(/[\b]/g, "\\b")
                        .replace(/[\n]/g, "\\n")
                        .replace(/[\t]/g, "\\t")
                        .replace(/[\r]/g, "\\r");
                }
            };
            return lang;
        });
        /**
         * prim 0.0.1 Copyright (c) 2012-2014, The Dojo Foundation All Rights Reserved.
         * Available via the MIT or new BSD license.
         * see: http://github.com/requirejs/prim for details
         */

        /*global setImmediate, process, setTimeout, define, module */

//Set prime.hideResolutionConflict = true to allow "resolution-races"
//in promise-tests to pass.
//Since the goal of prim is to be a small impl for trusted code, it is
//more important to normally throw in this case so that we can find
//logic errors quicker.

        var prim;
        (function () {
            'use strict';
            var op = Object.prototype,
                hasOwn = op.hasOwnProperty;

            function hasProp(obj, prop) {
                return hasOwn.call(obj, prop);
            }

            /**
             * Helper function for iterating over an array. If the func returns
             * a true value, it will break out of the loop.
             */
            function each(ary, func) {
                if (ary) {
                    var i;
                    for (i = 0; i < ary.length; i += 1) {
                        if (ary[i]) {
                            func(ary[i], i, ary);
                        }
                    }
                }
            }

            function check(p) {
                if (hasProp(p, 'e') || hasProp(p, 'v')) {
                    if (!prim.hideResolutionConflict) {
                        throw new Error('nope');
                    }
                    return false;
                }
                return true;
            }

            function notify(ary, value) {
                prim.nextTick(function () {
                    each(ary, function (item) {
                        item(value);
                    });
                });
            }

            prim = function prim() {
                var p,
                    ok = [],
                    fail = [];

                return (p = {
                    callback: function (yes, no) {
                        if (no) {
                            p.errback(no);
                        }

                        if (hasProp(p, 'v')) {
                            prim.nextTick(function () {
                                yes(p.v);
                            });
                        } else {
                            ok.push(yes);
                        }
                    },

                    errback: function (no) {
                        if (hasProp(p, 'e')) {
                            prim.nextTick(function () {
                                no(p.e);
                            });
                        } else {
                            fail.push(no);
                        }
                    },

                    finished: function () {
                        return hasProp(p, 'e') || hasProp(p, 'v');
                    },

                    rejected: function () {
                        return hasProp(p, 'e');
                    },

                    resolve: function (v) {
                        if (check(p)) {
                            p.v = v;
                            notify(ok, v);
                        }
                        return p;
                    },
                    reject: function (e) {
                        if (check(p)) {
                            p.e = e;
                            notify(fail, e);
                        }
                        return p;
                    },

                    start: function (fn) {
                        p.resolve();
                        return p.promise.then(fn);
                    },

                    promise: {
                        then: function (yes, no) {
                            var next = prim();

                            p.callback(function (v) {
                                try {
                                    if (yes && typeof yes === 'function') {
                                        v = yes(v);
                                    }

                                    if (v && v.then) {
                                        v.then(next.resolve, next.reject);
                                    } else {
                                        next.resolve(v);
                                    }
                                } catch (e) {
                                    next.reject(e);
                                }
                            }, function (e) {
                                var err;

                                try {
                                    if (!no || typeof no !== 'function') {
                                        next.reject(e);
                                    } else {
                                        err = no(e);

                                        if (err && err.then) {
                                            err.then(next.resolve, next.reject);
                                        } else {
                                            next.resolve(err);
                                        }
                                    }
                                } catch (e2) {
                                    next.reject(e2);
                                }
                            });

                            return next.promise;
                        },

                        fail: function (no) {
                            return p.promise.then(null, no);
                        },

                        end: function () {
                            p.errback(function (e) {
                                throw e;
                            });
                        }
                    }
                });
            };

            prim.serial = function (ary) {
                var result = prim().resolve().promise;
                each(ary, function (item) {
                    result = result.then(function () {
                        return item();
                    });
                });
                return result;
            };

            prim.nextTick = typeof setImmediate === 'function' ? setImmediate :
                (typeof process !== 'undefined' && process.nextTick ?
                    process.nextTick : (typeof setTimeout !== 'undefined' ?
                    function (fn) {
                        setTimeout(fn, 0);
                    } : function (fn) {
                    fn();
                }));

            if (typeof define === 'function' && define.amd) {
                define('prim', function () { return prim; });
            } else if (typeof module !== 'undefined' && module.exports) {
                module.exports = prim;
            }
        }());
        if(env === 'browser') {
            /**
             * @license RequireJS Copyright (c) 2012-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, load: false */

//Just a stub for use with uglify's consolidator.js
            define('browser/assert', function () {
                return {};
            });

        }

        if(env === 'node') {
            /**
             * @license RequireJS Copyright (c) 2012-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, load: false */

//Needed so that rhino/assert can return a stub for uglify's consolidator.js
            define('node/assert', ['assert'], function (assert) {
                return assert;
            });

        }

        if(env === 'rhino') {
            /**
             * @license RequireJS Copyright (c) 2013-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, load: false */

//Just a stub for use with uglify's consolidator.js
            define('rhino/assert', function () {
                return {};
            });

        }

        if(env === 'xpconnect') {
            /**
             * @license RequireJS Copyright (c) 2013-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, load: false */

//Just a stub for use with uglify's consolidator.js
            define('xpconnect/assert', function () {
                return {};
            });

        }

        if(env === 'browser') {
            /**
             * @license Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, process: false */

            define('browser/args', function () {
                //Always expect config via an API call
                return [];
            });

        }

        if(env === 'node') {
            /**
             * @license Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, process: false */

            define('node/args', function () {
                //Do not return the "node" or "r.js" arguments
                var args = process.argv.slice(2);

                //Ignore any command option used for main x.js branching
                if (args[0] && args[0].indexOf('-') === 0) {
                    args = args.slice(1);
                }

                return args;
            });

        }

        if(env === 'rhino') {
            /**
             * @license Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, process: false */

            var jsLibRhinoArgs = (typeof rhinoArgs !== 'undefined' && rhinoArgs) || [].concat(Array.prototype.slice.call(arguments, 0));

            define('rhino/args', function () {
                var args = jsLibRhinoArgs;

                //Ignore any command option used for main x.js branching
                if (args[0] && args[0].indexOf('-') === 0) {
                    args = args.slice(1);
                }

                return args;
            });

        }

        if(env === 'xpconnect') {
            /**
             * @license Copyright (c) 2013-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define, xpconnectArgs */

            var jsLibXpConnectArgs = (typeof xpconnectArgs !== 'undefined' && xpconnectArgs) || [].concat(Array.prototype.slice.call(arguments, 0));

            define('xpconnect/args', function () {
                var args = jsLibXpConnectArgs;

                //Ignore any command option used for main x.js branching
                if (args[0] && args[0].indexOf('-') === 0) {
                    args = args.slice(1);
                }

                return args;
            });

        }

        if(env === 'browser') {
            /**
             * @license RequireJS Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, console: false */

            define('browser/load', ['./file'], function (file) {
                function load(fileName) {
                    eval(file.readFile(fileName));
                }

                return load;
            });

        }

        if(env === 'node') {
            /**
             * @license RequireJS Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, console: false */

            define('node/load', ['fs'], function (fs) {
                function load(fileName) {
                    var contents = fs.readFileSync(fileName, 'utf8');
                    process.compile(contents, fileName);
                }

                return load;
            });

        }

        if(env === 'rhino') {
            /**
             * @license RequireJS Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, load: false */

            define('rhino/load', function () {
                return load;
            });

        }

        if(env === 'xpconnect') {
            /**
             * @license RequireJS Copyright (c) 2013-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, load: false */

            define('xpconnect/load', function () {
                return load;
            });

        }

        if(env === 'browser') {
            /**
             * @license Copyright (c) 2012-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint sloppy: true, nomen: true */
            /*global require, define, console, XMLHttpRequest, requirejs, location */

            define('browser/file', ['prim'], function (prim) {

                var file,
                    currDirRegExp = /^\.(\/|$)/;

                function frontSlash(path) {
                    return path.replace(/\\/g, '/');
                }

                function exists(path) {
                    var status, xhr = new XMLHttpRequest();

                    //Oh yeah, that is right SYNC IO. Behold its glory
                    //and horrible blocking behavior.
                    xhr.open('HEAD', path, false);
                    xhr.send();
                    status = xhr.status;

                    return status === 200 || status === 304;
                }

                function mkDir(dir) {
                    console.log('mkDir is no-op in browser');
                }

                function mkFullDir(dir) {
                    console.log('mkFullDir is no-op in browser');
                }

                file = {
                    backSlashRegExp: /\\/g,
                    exclusionRegExp: /^\./,
                    getLineSeparator: function () {
                        return '/';
                    },

                    exists: function (fileName) {
                        return exists(fileName);
                    },

                    parent: function (fileName) {
                        var parts = fileName.split('/');
                        parts.pop();
                        return parts.join('/');
                    },

                    /**
                     * Gets the absolute file path as a string, normalized
                     * to using front slashes for path separators.
                     * @param {String} fileName
                     */
                    absPath: function (fileName) {
                        var dir;
                        if (currDirRegExp.test(fileName)) {
                            dir = frontSlash(location.href);
                            if (dir.indexOf('/') !== -1) {
                                dir = dir.split('/');

                                //Pull off protocol and host, just want
                                //to allow paths (other build parts, like
                                //require._isSupportedBuildUrl do not support
                                //full URLs), but a full path from
                                //the root.
                                dir.splice(0, 3);

                                dir.pop();
                                dir = '/' + dir.join('/');
                            }

                            fileName = dir + fileName.substring(1);
                        }

                        return fileName;
                    },

                    normalize: function (fileName) {
                        return fileName;
                    },

                    isFile: function (path) {
                        return true;
                    },

                    isDirectory: function (path) {
                        return false;
                    },

                    getFilteredFileList: function (startDir, regExpFilters, makeUnixPaths) {
                        console.log('file.getFilteredFileList is no-op in browser');
                    },

                    copyDir: function (srcDir, destDir, regExpFilter, onlyCopyNew) {
                        console.log('file.copyDir is no-op in browser');

                    },

                    copyFile: function (srcFileName, destFileName, onlyCopyNew) {
                        console.log('file.copyFile is no-op in browser');
                    },

                    /**
                     * Renames a file. May fail if "to" already exists or is on another drive.
                     */
                    renameFile: function (from, to) {
                        console.log('file.renameFile is no-op in browser');
                    },

                    /**
                     * Reads a *text* file.
                     */
                    readFile: function (path, encoding) {
                        var xhr = new XMLHttpRequest();

                        //Oh yeah, that is right SYNC IO. Behold its glory
                        //and horrible blocking behavior.
                        xhr.open('GET', path, false);
                        xhr.send();

                        return xhr.responseText;
                    },

                    readFileAsync: function (path, encoding) {
                        var xhr = new XMLHttpRequest(),
                            d = prim();

                        xhr.open('GET', path, true);
                        xhr.send();

                        xhr.onreadystatechange = function () {
                            if (xhr.readyState === 4) {
                                if (xhr.status > 400) {
                                    d.reject(new Error('Status: ' + xhr.status + ': ' + xhr.statusText));
                                } else {
                                    d.resolve(xhr.responseText);
                                }
                            }
                        };

                        return d.promise;
                    },

                    saveUtf8File: function (fileName, fileContents) {
                        //summary: saves a *text* file using UTF-8 encoding.
                        file.saveFile(fileName, fileContents, "utf8");
                    },

                    saveFile: function (fileName, fileContents, encoding) {
                        requirejs.browser.saveFile(fileName, fileContents, encoding);
                    },

                    deleteFile: function (fileName) {
                        console.log('file.deleteFile is no-op in browser');
                    },

                    /**
                     * Deletes any empty directories under the given directory.
                     */
                    deleteEmptyDirs: function (startDir) {
                        console.log('file.deleteEmptyDirs is no-op in browser');
                    }
                };

                return file;

            });

        }

        if(env === 'node') {
            /**
             * @license Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint plusplus: false, octal:false, strict: false */
            /*global define: false, process: false */

            define('node/file', ['fs', 'path', 'prim'], function (fs, path, prim) {

                var isWindows = process.platform === 'win32',
                    windowsDriveRegExp = /^[a-zA-Z]\:\/$/,
                    file;

                function frontSlash(path) {
                    return path.replace(/\\/g, '/');
                }

                function exists(path) {
                    if (isWindows && path.charAt(path.length - 1) === '/' &&
                        path.charAt(path.length - 2) !== ':') {
                        path = path.substring(0, path.length - 1);
                    }

                    try {
                        fs.statSync(path);
                        return true;
                    } catch (e) {
                        return false;
                    }
                }

                function mkDir(dir) {
                    if (!exists(dir) && (!isWindows || !windowsDriveRegExp.test(dir))) {
                        fs.mkdirSync(dir, 511);
                    }
                }

                function mkFullDir(dir) {
                    var parts = dir.split('/'),
                        currDir = '',
                        first = true;

                    parts.forEach(function (part) {
                        //First part may be empty string if path starts with a slash.
                        currDir += part + '/';
                        first = false;

                        if (part) {
                            mkDir(currDir);
                        }
                    });
                }

                file = {
                    backSlashRegExp: /\\/g,
                    exclusionRegExp: /^\./,
                    getLineSeparator: function () {
                        return '/';
                    },

                    exists: function (fileName) {
                        return exists(fileName);
                    },

                    parent: function (fileName) {
                        var parts = fileName.split('/');
                        parts.pop();
                        return parts.join('/');
                    },

                    /**
                     * Gets the absolute file path as a string, normalized
                     * to using front slashes for path separators.
                     * @param {String} fileName
                     */
                    absPath: function (fileName) {
                        return frontSlash(path.normalize(frontSlash(fs.realpathSync(fileName))));
                    },

                    normalize: function (fileName) {
                        return frontSlash(path.normalize(fileName));
                    },

                    isFile: function (path) {
                        return fs.statSync(path).isFile();
                    },

                    isDirectory: function (path) {
                        return fs.statSync(path).isDirectory();
                    },

                    getFilteredFileList: function (/*String*/startDir, /*RegExp*/regExpFilters, /*boolean?*/makeUnixPaths) {
                        //summary: Recurses startDir and finds matches to the files that match regExpFilters.include
                        //and do not match regExpFilters.exclude. Or just one regexp can be passed in for regExpFilters,
                        //and it will be treated as the "include" case.
                        //Ignores files/directories that start with a period (.) unless exclusionRegExp
                        //is set to another value.
                        var files = [], topDir, regExpInclude, regExpExclude, dirFileArray,
                            i, stat, filePath, ok, dirFiles, fileName;

                        topDir = startDir;

                        regExpInclude = regExpFilters.include || regExpFilters;
                        regExpExclude = regExpFilters.exclude || null;

                        if (file.exists(topDir)) {
                            dirFileArray = fs.readdirSync(topDir);
                            for (i = 0; i < dirFileArray.length; i++) {
                                fileName = dirFileArray[i];
                                filePath = path.join(topDir, fileName);
                                stat = fs.statSync(filePath);
                                if (stat.isFile()) {
                                    if (makeUnixPaths) {
                                        //Make sure we have a JS string.
                                        if (filePath.indexOf("/") === -1) {
                                            filePath = frontSlash(filePath);
                                        }
                                    }

                                    ok = true;
                                    if (regExpInclude) {
                                        ok = filePath.match(regExpInclude);
                                    }
                                    if (ok && regExpExclude) {
                                        ok = !filePath.match(regExpExclude);
                                    }

                                    if (ok && (!file.exclusionRegExp ||
                                        !file.exclusionRegExp.test(fileName))) {
                                        files.push(filePath);
                                    }
                                } else if (stat.isDirectory() &&
                                    (!file.exclusionRegExp || !file.exclusionRegExp.test(fileName))) {
                                    dirFiles = this.getFilteredFileList(filePath, regExpFilters, makeUnixPaths);
                                    files.push.apply(files, dirFiles);
                                }
                            }
                        }

                        return files; //Array
                    },

                    copyDir: function (/*String*/srcDir, /*String*/destDir, /*RegExp?*/regExpFilter, /*boolean?*/onlyCopyNew) {
                        //summary: copies files from srcDir to destDir using the regExpFilter to determine if the
                        //file should be copied. Returns a list file name strings of the destinations that were copied.
                        regExpFilter = regExpFilter || /\w/;

                        //Normalize th directory names, but keep front slashes.
                        //path module on windows now returns backslashed paths.
                        srcDir = frontSlash(path.normalize(srcDir));
                        destDir = frontSlash(path.normalize(destDir));

                        var fileNames = file.getFilteredFileList(srcDir, regExpFilter, true),
                            copiedFiles = [], i, srcFileName, destFileName;

                        for (i = 0; i < fileNames.length; i++) {
                            srcFileName = fileNames[i];
                            destFileName = srcFileName.replace(srcDir, destDir);

                            if (file.copyFile(srcFileName, destFileName, onlyCopyNew)) {
                                copiedFiles.push(destFileName);
                            }
                        }

                        return copiedFiles.length ? copiedFiles : null; //Array or null
                    },

                    copyFile: function (/*String*/srcFileName, /*String*/destFileName, /*boolean?*/onlyCopyNew) {
                        //summary: copies srcFileName to destFileName. If onlyCopyNew is set, it only copies the file if
                        //srcFileName is newer than destFileName. Returns a boolean indicating if the copy occurred.
                        var parentDir;

                        //logger.trace("Src filename: " + srcFileName);
                        //logger.trace("Dest filename: " + destFileName);

                        //If onlyCopyNew is true, then compare dates and only copy if the src is newer
                        //than dest.
                        if (onlyCopyNew) {
                            if (file.exists(destFileName) && fs.statSync(destFileName).mtime.getTime() >= fs.statSync(srcFileName).mtime.getTime()) {
                                return false; //Boolean
                            }
                        }

                        //Make sure destination dir exists.
                        parentDir = path.dirname(destFileName);
                        if (!file.exists(parentDir)) {
                            mkFullDir(parentDir);
                        }

                        fs.writeFileSync(destFileName, fs.readFileSync(srcFileName, 'binary'), 'binary');

                        return true; //Boolean
                    },

                    /**
                     * Renames a file. May fail if "to" already exists or is on another drive.
                     */
                    renameFile: function (from, to) {
                        return fs.renameSync(from, to);
                    },

                    /**
                     * Reads a *text* file.
                     */
                    readFile: function (/*String*/path, /*String?*/encoding) {
                        if (encoding === 'utf-8') {
                            encoding = 'utf8';
                        }
                        if (!encoding) {
                            encoding = 'utf8';
                        }

                        var text = fs.readFileSync(path, encoding);

                        //Hmm, would not expect to get A BOM, but it seems to happen,
                        //remove it just in case.
                        if (text.indexOf('\uFEFF') === 0) {
                            text = text.substring(1, text.length);
                        }

                        return text;
                    },

                    readFileAsync: function (path, encoding) {
                        var d = prim();
                        try {
                            d.resolve(file.readFile(path, encoding));
                        } catch (e) {
                            d.reject(e);
                        }
                        return d.promise;
                    },

                    saveUtf8File: function (/*String*/fileName, /*String*/fileContents) {
                        //summary: saves a *text* file using UTF-8 encoding.
                        file.saveFile(fileName, fileContents, "utf8");
                    },

                    saveFile: function (/*String*/fileName, /*String*/fileContents, /*String?*/encoding) {
                        //summary: saves a *text* file.
                        var parentDir;

                        if (encoding === 'utf-8') {
                            encoding = 'utf8';
                        }
                        if (!encoding) {
                            encoding = 'utf8';
                        }

                        //Make sure destination directories exist.
                        parentDir = path.dirname(fileName);
                        if (!file.exists(parentDir)) {
                            mkFullDir(parentDir);
                        }

                        fs.writeFileSync(fileName, fileContents, encoding);
                    },

                    deleteFile: function (/*String*/fileName) {
                        //summary: deletes a file or directory if it exists.
                        var files, i, stat;
                        if (file.exists(fileName)) {
                            stat = fs.lstatSync(fileName);
                            if (stat.isDirectory()) {
                                files = fs.readdirSync(fileName);
                                for (i = 0; i < files.length; i++) {
                                    this.deleteFile(path.join(fileName, files[i]));
                                }
                                fs.rmdirSync(fileName);
                            } else {
                                fs.unlinkSync(fileName);
                            }
                        }
                    },


                    /**
                     * Deletes any empty directories under the given directory.
                     */
                    deleteEmptyDirs: function (startDir) {
                        var dirFileArray, i, fileName, filePath, stat;

                        if (file.exists(startDir)) {
                            dirFileArray = fs.readdirSync(startDir);
                            for (i = 0; i < dirFileArray.length; i++) {
                                fileName = dirFileArray[i];
                                filePath = path.join(startDir, fileName);
                                stat = fs.lstatSync(filePath);
                                if (stat.isDirectory()) {
                                    file.deleteEmptyDirs(filePath);
                                }
                            }

                            //If directory is now empty, remove it.
                            if (fs.readdirSync(startDir).length ===  0) {
                                file.deleteFile(startDir);
                            }
                        }
                    }
                };

                return file;

            });

        }

        if(env === 'rhino') {
            /**
             * @license RequireJS Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */
//Helper functions to deal with file I/O.

            /*jslint plusplus: false */
            /*global java: false, define: false */

            define('rhino/file', ['prim'], function (prim) {
                var file = {
                    backSlashRegExp: /\\/g,

                    exclusionRegExp: /^\./,

                    getLineSeparator: function () {
                        return file.lineSeparator;
                    },

                    lineSeparator: java.lang.System.getProperty("line.separator"), //Java String

                    exists: function (fileName) {
                        return (new java.io.File(fileName)).exists();
                    },

                    parent: function (fileName) {
                        return file.absPath((new java.io.File(fileName)).getParentFile());
                    },

                    normalize: function (fileName) {
                        return file.absPath(fileName);
                    },

                    isFile: function (path) {
                        return (new java.io.File(path)).isFile();
                    },

                    isDirectory: function (path) {
                        return (new java.io.File(path)).isDirectory();
                    },

                    /**
                     * Gets the absolute file path as a string, normalized
                     * to using front slashes for path separators.
                     * @param {java.io.File||String} file
                     */
                    absPath: function (fileObj) {
                        if (typeof fileObj === "string") {
                            fileObj = new java.io.File(fileObj);
                        }
                        return (fileObj.getCanonicalPath() + "").replace(file.backSlashRegExp, "/");
                    },

                    getFilteredFileList: function (/*String*/startDir, /*RegExp*/regExpFilters, /*boolean?*/makeUnixPaths, /*boolean?*/startDirIsJavaObject) {
                        //summary: Recurses startDir and finds matches to the files that match regExpFilters.include
                        //and do not match regExpFilters.exclude. Or just one regexp can be passed in for regExpFilters,
                        //and it will be treated as the "include" case.
                        //Ignores files/directories that start with a period (.) unless exclusionRegExp
                        //is set to another value.
                        var files = [], topDir, regExpInclude, regExpExclude, dirFileArray,
                            i, fileObj, filePath, ok, dirFiles;

                        topDir = startDir;
                        if (!startDirIsJavaObject) {
                            topDir = new java.io.File(startDir);
                        }

                        regExpInclude = regExpFilters.include || regExpFilters;
                        regExpExclude = regExpFilters.exclude || null;

                        if (topDir.exists()) {
                            dirFileArray = topDir.listFiles();
                            for (i = 0; i < dirFileArray.length; i++) {
                                fileObj = dirFileArray[i];
                                if (fileObj.isFile()) {
                                    filePath = fileObj.getPath();
                                    if (makeUnixPaths) {
                                        //Make sure we have a JS string.
                                        filePath = String(filePath);
                                        if (filePath.indexOf("/") === -1) {
                                            filePath = filePath.replace(/\\/g, "/");
                                        }
                                    }

                                    ok = true;
                                    if (regExpInclude) {
                                        ok = filePath.match(regExpInclude);
                                    }
                                    if (ok && regExpExclude) {
                                        ok = !filePath.match(regExpExclude);
                                    }

                                    if (ok && (!file.exclusionRegExp ||
                                        !file.exclusionRegExp.test(fileObj.getName()))) {
                                        files.push(filePath);
                                    }
                                } else if (fileObj.isDirectory() &&
                                    (!file.exclusionRegExp || !file.exclusionRegExp.test(fileObj.getName()))) {
                                    dirFiles = this.getFilteredFileList(fileObj, regExpFilters, makeUnixPaths, true);
                                    files.push.apply(files, dirFiles);
                                }
                            }
                        }

                        return files; //Array
                    },

                    copyDir: function (/*String*/srcDir, /*String*/destDir, /*RegExp?*/regExpFilter, /*boolean?*/onlyCopyNew) {
                        //summary: copies files from srcDir to destDir using the regExpFilter to determine if the
                        //file should be copied. Returns a list file name strings of the destinations that were copied.
                        regExpFilter = regExpFilter || /\w/;

                        var fileNames = file.getFilteredFileList(srcDir, regExpFilter, true),
                            copiedFiles = [], i, srcFileName, destFileName;

                        for (i = 0; i < fileNames.length; i++) {
                            srcFileName = fileNames[i];
                            destFileName = srcFileName.replace(srcDir, destDir);

                            if (file.copyFile(srcFileName, destFileName, onlyCopyNew)) {
                                copiedFiles.push(destFileName);
                            }
                        }

                        return copiedFiles.length ? copiedFiles : null; //Array or null
                    },

                    copyFile: function (/*String*/srcFileName, /*String*/destFileName, /*boolean?*/onlyCopyNew) {
                        //summary: copies srcFileName to destFileName. If onlyCopyNew is set, it only copies the file if
                        //srcFileName is newer than destFileName. Returns a boolean indicating if the copy occurred.
                        var destFile = new java.io.File(destFileName), srcFile, parentDir,
                            srcChannel, destChannel;

                        //logger.trace("Src filename: " + srcFileName);
                        //logger.trace("Dest filename: " + destFileName);

                        //If onlyCopyNew is true, then compare dates and only copy if the src is newer
                        //than dest.
                        if (onlyCopyNew) {
                            srcFile = new java.io.File(srcFileName);
                            if (destFile.exists() && destFile.lastModified() >= srcFile.lastModified()) {
                                return false; //Boolean
                            }
                        }

                        //Make sure destination dir exists.
                        parentDir = destFile.getParentFile();
                        if (!parentDir.exists()) {
                            if (!parentDir.mkdirs()) {
                                throw "Could not create directory: " + parentDir.getCanonicalPath();
                            }
                        }

                        //Java's version of copy file.
                        srcChannel = new java.io.FileInputStream(srcFileName).getChannel();
                        destChannel = new java.io.FileOutputStream(destFileName).getChannel();
                        destChannel.transferFrom(srcChannel, 0, srcChannel.size());
                        srcChannel.close();
                        destChannel.close();

                        return true; //Boolean
                    },

                    /**
                     * Renames a file. May fail if "to" already exists or is on another drive.
                     */
                    renameFile: function (from, to) {
                        return (new java.io.File(from)).renameTo((new java.io.File(to)));
                    },

                    readFile: function (/*String*/path, /*String?*/encoding) {
                        //A file read function that can deal with BOMs
                        encoding = encoding || "utf-8";
                        var fileObj = new java.io.File(path),
                            input = new java.io.BufferedReader(new java.io.InputStreamReader(new java.io.FileInputStream(fileObj), encoding)),
                            stringBuffer, line;
                        try {
                            stringBuffer = new java.lang.StringBuffer();
                            line = input.readLine();

                            // Byte Order Mark (BOM) - The Unicode Standard, version 3.0, page 324
                            // http://www.unicode.org/faq/utf_bom.html

                            // Note that when we use utf-8, the BOM should appear as "EF BB BF", but it doesn't due to this bug in the JDK:
                            // http://bugs.sun.com/bugdatabase/view_bug.do?bug_id=4508058
                            if (line && line.length() && line.charAt(0) === 0xfeff) {
                                // Eat the BOM, since we've already found the encoding on this file,
                                // and we plan to concatenating this buffer with others; the BOM should
                                // only appear at the top of a file.
                                line = line.substring(1);
                            }
                            while (line !== null) {
                                stringBuffer.append(line);
                                stringBuffer.append(file.lineSeparator);
                                line = input.readLine();
                            }
                            //Make sure we return a JavaScript string and not a Java string.
                            return String(stringBuffer.toString()); //String
                        } finally {
                            input.close();
                        }
                    },

                    readFileAsync: function (path, encoding) {
                        var d = prim();
                        try {
                            d.resolve(file.readFile(path, encoding));
                        } catch (e) {
                            d.reject(e);
                        }
                        return d.promise;
                    },

                    saveUtf8File: function (/*String*/fileName, /*String*/fileContents) {
                        //summary: saves a file using UTF-8 encoding.
                        file.saveFile(fileName, fileContents, "utf-8");
                    },

                    saveFile: function (/*String*/fileName, /*String*/fileContents, /*String?*/encoding) {
                        //summary: saves a file.
                        var outFile = new java.io.File(fileName), outWriter, parentDir, os;

                        parentDir = outFile.getAbsoluteFile().getParentFile();
                        if (!parentDir.exists()) {
                            if (!parentDir.mkdirs()) {
                                throw "Could not create directory: " + parentDir.getAbsolutePath();
                            }
                        }

                        if (encoding) {
                            outWriter = new java.io.OutputStreamWriter(new java.io.FileOutputStream(outFile), encoding);
                        } else {
                            outWriter = new java.io.OutputStreamWriter(new java.io.FileOutputStream(outFile));
                        }

                        os = new java.io.BufferedWriter(outWriter);
                        try {
                            os.write(fileContents);
                        } finally {
                            os.close();
                        }
                    },

                    deleteFile: function (/*String*/fileName) {
                        //summary: deletes a file or directory if it exists.
                        var fileObj = new java.io.File(fileName), files, i;
                        if (fileObj.exists()) {
                            if (fileObj.isDirectory()) {
                                files = fileObj.listFiles();
                                for (i = 0; i < files.length; i++) {
                                    this.deleteFile(files[i]);
                                }
                            }
                            fileObj["delete"]();
                        }
                    },

                    /**
                     * Deletes any empty directories under the given directory.
                     * The startDirIsJavaObject is private to this implementation's
                     * recursion needs.
                     */
                    deleteEmptyDirs: function (startDir, startDirIsJavaObject) {
                        var topDir = startDir,
                            dirFileArray, i, fileObj;

                        if (!startDirIsJavaObject) {
                            topDir = new java.io.File(startDir);
                        }

                        if (topDir.exists()) {
                            dirFileArray = topDir.listFiles();
                            for (i = 0; i < dirFileArray.length; i++) {
                                fileObj = dirFileArray[i];
                                if (fileObj.isDirectory()) {
                                    file.deleteEmptyDirs(fileObj, true);
                                }
                            }

                            //If the directory is empty now, delete it.
                            if (topDir.listFiles().length === 0) {
                                file.deleteFile(String(topDir.getPath()));
                            }
                        }
                    }
                };

                return file;
            });

        }

        if(env === 'xpconnect') {
            /**
             * @license RequireJS Copyright (c) 2013-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */
//Helper functions to deal with file I/O.

            /*jslint plusplus: false */
            /*global define, Components, xpcUtil */

            define('xpconnect/file', ['prim'], function (prim) {
                var file,
                    Cc = Components.classes,
                    Ci = Components.interfaces,
                //Depends on xpcUtil which is set up in x.js
                    xpfile = xpcUtil.xpfile;

                function mkFullDir(dirObj) {
                    //1 is DIRECTORY_TYPE, 511 is 0777 permissions
                    if (!dirObj.exists()) {
                        dirObj.create(1, 511);
                    }
                }

                file = {
                    backSlashRegExp: /\\/g,

                    exclusionRegExp: /^\./,

                    getLineSeparator: function () {
                        return file.lineSeparator;
                    },

                    lineSeparator: ('@mozilla.org/windows-registry-key;1' in Cc) ?
                        '\r\n' : '\n',

                    exists: function (fileName) {
                        return xpfile(fileName).exists();
                    },

                    parent: function (fileName) {
                        return xpfile(fileName).parent;
                    },

                    normalize: function (fileName) {
                        return file.absPath(fileName);
                    },

                    isFile: function (path) {
                        return xpfile(path).isFile();
                    },

                    isDirectory: function (path) {
                        return xpfile(path).isDirectory();
                    },

                    /**
                     * Gets the absolute file path as a string, normalized
                     * to using front slashes for path separators.
                     * @param {java.io.File||String} file
                     */
                    absPath: function (fileObj) {
                        if (typeof fileObj === "string") {
                            fileObj = xpfile(fileObj);
                        }
                        return fileObj.path;
                    },

                    getFilteredFileList: function (/*String*/startDir, /*RegExp*/regExpFilters, /*boolean?*/makeUnixPaths, /*boolean?*/startDirIsObject) {
                        //summary: Recurses startDir and finds matches to the files that match regExpFilters.include
                        //and do not match regExpFilters.exclude. Or just one regexp can be passed in for regExpFilters,
                        //and it will be treated as the "include" case.
                        //Ignores files/directories that start with a period (.) unless exclusionRegExp
                        //is set to another value.
                        var files = [], topDir, regExpInclude, regExpExclude, dirFileArray,
                            fileObj, filePath, ok, dirFiles;

                        topDir = startDir;
                        if (!startDirIsObject) {
                            topDir = xpfile(startDir);
                        }

                        regExpInclude = regExpFilters.include || regExpFilters;
                        regExpExclude = regExpFilters.exclude || null;

                        if (topDir.exists()) {
                            dirFileArray = topDir.directoryEntries;
                            while (dirFileArray.hasMoreElements()) {
                                fileObj = dirFileArray.getNext().QueryInterface(Ci.nsILocalFile);
                                if (fileObj.isFile()) {
                                    filePath = fileObj.path;
                                    if (makeUnixPaths) {
                                        if (filePath.indexOf("/") === -1) {
                                            filePath = filePath.replace(/\\/g, "/");
                                        }
                                    }

                                    ok = true;
                                    if (regExpInclude) {
                                        ok = filePath.match(regExpInclude);
                                    }
                                    if (ok && regExpExclude) {
                                        ok = !filePath.match(regExpExclude);
                                    }

                                    if (ok && (!file.exclusionRegExp ||
                                        !file.exclusionRegExp.test(fileObj.leafName))) {
                                        files.push(filePath);
                                    }
                                } else if (fileObj.isDirectory() &&
                                    (!file.exclusionRegExp || !file.exclusionRegExp.test(fileObj.leafName))) {
                                    dirFiles = this.getFilteredFileList(fileObj, regExpFilters, makeUnixPaths, true);
                                    files.push.apply(files, dirFiles);
                                }
                            }
                        }

                        return files; //Array
                    },

                    copyDir: function (/*String*/srcDir, /*String*/destDir, /*RegExp?*/regExpFilter, /*boolean?*/onlyCopyNew) {
                        //summary: copies files from srcDir to destDir using the regExpFilter to determine if the
                        //file should be copied. Returns a list file name strings of the destinations that were copied.
                        regExpFilter = regExpFilter || /\w/;

                        var fileNames = file.getFilteredFileList(srcDir, regExpFilter, true),
                            copiedFiles = [], i, srcFileName, destFileName;

                        for (i = 0; i < fileNames.length; i += 1) {
                            srcFileName = fileNames[i];
                            destFileName = srcFileName.replace(srcDir, destDir);

                            if (file.copyFile(srcFileName, destFileName, onlyCopyNew)) {
                                copiedFiles.push(destFileName);
                            }
                        }

                        return copiedFiles.length ? copiedFiles : null; //Array or null
                    },

                    copyFile: function (/*String*/srcFileName, /*String*/destFileName, /*boolean?*/onlyCopyNew) {
                        //summary: copies srcFileName to destFileName. If onlyCopyNew is set, it only copies the file if
                        //srcFileName is newer than destFileName. Returns a boolean indicating if the copy occurred.
                        var destFile = xpfile(destFileName),
                            srcFile = xpfile(srcFileName);

                        //logger.trace("Src filename: " + srcFileName);
                        //logger.trace("Dest filename: " + destFileName);

                        //If onlyCopyNew is true, then compare dates and only copy if the src is newer
                        //than dest.
                        if (onlyCopyNew) {
                            if (destFile.exists() && destFile.lastModifiedTime >= srcFile.lastModifiedTime) {
                                return false; //Boolean
                            }
                        }

                        srcFile.copyTo(destFile.parent, destFile.leafName);

                        return true; //Boolean
                    },

                    /**
                     * Renames a file. May fail if "to" already exists or is on another drive.
                     */
                    renameFile: function (from, to) {
                        var toFile = xpfile(to);
                        return xpfile(from).moveTo(toFile.parent, toFile.leafName);
                    },

                    readFile: xpcUtil.readFile,

                    readFileAsync: function (path, encoding) {
                        var d = prim();
                        try {
                            d.resolve(file.readFile(path, encoding));
                        } catch (e) {
                            d.reject(e);
                        }
                        return d.promise;
                    },

                    saveUtf8File: function (/*String*/fileName, /*String*/fileContents) {
                        //summary: saves a file using UTF-8 encoding.
                        file.saveFile(fileName, fileContents, "utf-8");
                    },

                    saveFile: function (/*String*/fileName, /*String*/fileContents, /*String?*/encoding) {
                        var outStream, convertStream,
                            fileObj = xpfile(fileName);

                        mkFullDir(fileObj.parent);

                        try {
                            outStream = Cc['@mozilla.org/network/file-output-stream;1']
                                .createInstance(Ci.nsIFileOutputStream);
                            //438 is decimal for 0777
                            outStream.init(fileObj, 0x02 | 0x08 | 0x20, 511, 0);

                            convertStream = Cc['@mozilla.org/intl/converter-output-stream;1']
                                .createInstance(Ci.nsIConverterOutputStream);

                            convertStream.init(outStream, encoding, 0, 0);
                            convertStream.writeString(fileContents);
                        } catch (e) {
                            throw new Error((fileObj && fileObj.path || '') + ': ' + e);
                        } finally {
                            if (convertStream) {
                                convertStream.close();
                            }
                            if (outStream) {
                                outStream.close();
                            }
                        }
                    },

                    deleteFile: function (/*String*/fileName) {
                        //summary: deletes a file or directory if it exists.
                        var fileObj = xpfile(fileName);
                        if (fileObj.exists()) {
                            fileObj.remove(true);
                        }
                    },

                    /**
                     * Deletes any empty directories under the given directory.
                     * The startDirIsJavaObject is private to this implementation's
                     * recursion needs.
                     */
                    deleteEmptyDirs: function (startDir, startDirIsObject) {
                        var topDir = startDir,
                            dirFileArray, fileObj;

                        if (!startDirIsObject) {
                            topDir = xpfile(startDir);
                        }

                        if (topDir.exists()) {
                            dirFileArray = topDir.directoryEntries;
                            while (dirFileArray.hasMoreElements()) {
                                fileObj = dirFileArray.getNext().QueryInterface(Ci.nsILocalFile);

                                if (fileObj.isDirectory()) {
                                    file.deleteEmptyDirs(fileObj, true);
                                }
                            }

                            //If the directory is empty now, delete it.
                            dirFileArray = topDir.directoryEntries;
                            if (!dirFileArray.hasMoreElements()) {
                                file.deleteFile(topDir.path);
                            }
                        }
                    }
                };

                return file;
            });

        }

        if(env === 'browser') {
            /*global process */
            define('browser/quit', function () {
                'use strict';
                return function (code) {
                };
            });
        }

        if(env === 'node') {
            /*global process */
            define('node/quit', function () {
                'use strict';
                return function (code) {
                    var draining = 0;
                    var exit = function () {
                        if (draining === 0) {
                            process.exit(code);
                        } else {
                            draining -= 1;
                        }
                    };
                    if (process.stdout.bufferSize) {
                        draining += 1;
                        process.stdout.once('drain', exit);
                    }
                    if (process.stderr.bufferSize) {
                        draining += 1;
                        process.stderr.once('drain', exit);
                    }
                    exit();
                };
            });

        }

        if(env === 'rhino') {
            /*global quit */
            define('rhino/quit', function () {
                'use strict';
                return function (code) {
                    return quit(code);
                };
            });

        }

        if(env === 'xpconnect') {
            /*global quit */
            define('xpconnect/quit', function () {
                'use strict';
                return function (code) {
                    return quit(code);
                };
            });

        }

        if(env === 'browser') {
            /**
             * @license RequireJS Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, console: false */

            define('browser/print', function () {
                function print(msg) {
                    console.log(msg);
                }

                return print;
            });

        }

        if(env === 'node') {
            /**
             * @license RequireJS Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, console: false */

            define('node/print', function () {
                function print(msg) {
                    console.log(msg);
                }

                return print;
            });

        }

        if(env === 'rhino') {
            /**
             * @license RequireJS Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, print: false */

            define('rhino/print', function () {
                return print;
            });

        }

        if(env === 'xpconnect') {
            /**
             * @license RequireJS Copyright (c) 2013-2014, The Dojo Foundation All Rights Reserved.
             * Available via the MIT or new BSD license.
             * see: http://github.com/jrburke/requirejs for details
             */

            /*jslint strict: false */
            /*global define: false, print: false */

            define('xpconnect/print', function () {
                return print;
            });

        }
        /**
         * @license RequireJS Copyright (c) 2010-2014, The Dojo Foundation All Rights Reserved.
         * Available via the MIT or new BSD license.
         * see: http://github.com/jrburke/requirejs for details
         */

        /*jslint nomen: false, strict: false */
        /*global define: false */

        define('logger', ['env!env/print'], function (print) {
            var logger = {
                TRACE: 0,
                INFO: 1,
                WARN: 2,
                ERROR: 3,
                SILENT: 4,
                level: 0,
                logPrefix: "",

                logLevel: function( level ) {
                    this.level = level;
                },

                trace: function (message) {
                    if (this.level <= this.TRACE) {
                        this._print(message);
                    }
                },

                info: function (message) {
                    if (this.level <= this.INFO) {
                        this._print(message);
                    }
                },

                warn: function (message) {
                    if (this.level <= this.WARN) {
                        this._print(message);
                    }
                },

                error: function (message) {
                    if (this.level <= this.ERROR) {
                        this._print(message);
                    }
                },

                _print: function (message) {
                    this._sysPrint((this.logPrefix ? (this.logPrefix + " ") : "") + message);
                },

                _sysPrint: function (message) {
                    print(message);
                }
            };

            return logger;
        });
//Just a blank file to use when building the optimizer with the optimizer,
//so that the build does not attempt to inline some env modules,
//like Node's fs and path.

        /*
         Copyright (C) 2013 Ariya Hidayat <ariya.hidayat@gmail.com>
         Copyright (C) 2013 Thaddee Tyl <thaddee.tyl@gmail.com>
         Copyright (C) 2013 Mathias Bynens <mathias@qiwi.be>
         Copyright (C) 2012 Ariya Hidayat <ariya.hidayat@gmail.com>
         Copyright (C) 2012 Mathias Bynens <mathias@qiwi.be>
         Copyright (C) 2012 Joost-Wim Boekesteijn <joost-wim@boekesteijn.nl>
         Copyright (C) 2012 Kris Kowal <kris.kowal@cixar.com>
         Copyright (C) 2012 Yusuke Suzuki <utatane.tea@gmail.com>
         Copyright (C) 2012 Arpad Borsos <arpad.borsos@googlemail.com>
         Copyright (C) 2011 Ariya Hidayat <ariya.hidayat@gmail.com>

         Redistribution and use in source and binary forms, with or without
         modification, are permitted provided that the following conditions are met:

         * Redistributions of source code must retain the above copyright
         notice, this list of conditions and the following disclaimer.
         * Redistributions in binary form must reproduce the above copyright
         notice, this list of conditions and the following disclaimer in the
         documentation and/or other materials provided with the distribution.

         THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
         AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
         IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
         ARE DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
         DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
         (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
         LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
         ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
         (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
         THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
         */

        /*jslint bitwise:true plusplus:true */
        /*global esprima:true, define:true, exports:true, window: true,
         throwErrorTolerant: true,
         throwError: true, generateStatement: true, peek: true,
         parseAssignmentExpression: true, parseBlock: true, parseExpression: true,
         parseFunctionDeclaration: true, parseFunctionExpression: true,
         parseFunctionSourceElements: true, parseVariableIdentifier: true,
         parseLeftHandSideExpression: true,
         parseUnaryExpression: true,
         parseStatement: true, parseSourceElement: true */

        (function (root, factory) {
            'use strict';

            // Universal Module Definition (UMD) to support AMD, CommonJS/Node.js,
            // Rhino, and plain browser loading.

            /* istanbul ignore next */
            if (typeof define === 'function' && define.amd) {
                define('esprima', ['exports'], factory);
            } else if (typeof exports !== 'undefined') {
                factory(exports);
            } else {
                factory((root.esprima = {}));
            }
        }(this, function (exports) {
            'use strict';

            var Token,
                TokenName,
                FnExprTokens,
                Syntax,
                PropertyKind,
                Messages,
                Regex,
                SyntaxTreeDelegate,
                source,
                strict,
                index,
                lineNumber,
                lineStart,
                length,
                delegate,
                lookahead,
                state,
                extra;

            Token = {
                BooleanLiteral: 1,
                EOF: 2,
                Identifier: 3,
                Keyword: 4,
                NullLiteral: 5,
                NumericLiteral: 6,
                Punctuator: 7,
                StringLiteral: 8,
                RegularExpression: 9
            };

            TokenName = {};
            TokenName[Token.BooleanLiteral] = 'Boolean';
            TokenName[Token.EOF] = '<end>';
            TokenName[Token.Identifier] = 'Identifier';
            TokenName[Token.Keyword] = 'Keyword';
            TokenName[Token.NullLiteral] = 'Null';
            TokenName[Token.NumericLiteral] = 'Numeric';
            TokenName[Token.Punctuator] = 'Punctuator';
            TokenName[Token.StringLiteral] = 'String';
            TokenName[Token.RegularExpression] = 'RegularExpression';

            // A function following one of those tokens is an expression.
            FnExprTokens = ['(', '{', '[', 'in', 'typeof', 'instanceof', 'new',
                'return', 'case', 'delete', 'throw', 'void',
                // assignment operators
                '=', '+=', '-=', '*=', '/=', '%=', '<<=', '>>=', '>>>=',
                '&=', '|=', '^=', ',',
                // binary/unary operators
                '+', '-', '*', '/', '%', '++', '--', '<<', '>>', '>>>', '&',
                '|', '^', '!', '~', '&&', '||', '?', ':', '===', '==', '>=',
                '<=', '<', '>', '!=', '!=='];

            Syntax = {
                AssignmentExpression: 'AssignmentExpression',
                ArrayExpression: 'ArrayExpression',
                BlockStatement: 'BlockStatement',
                BinaryExpression: 'BinaryExpression',
                BreakStatement: 'BreakStatement',
                CallExpression: 'CallExpression',
                CatchClause: 'CatchClause',
                ConditionalExpression: 'ConditionalExpression',
                ContinueStatement: 'ContinueStatement',
                DoWhileStatement: 'DoWhileStatement',
                DebuggerStatement: 'DebuggerStatement',
                EmptyStatement: 'EmptyStatement',
                ExpressionStatement: 'ExpressionStatement',
                ForStatement: 'ForStatement',
                ForInStatement: 'ForInStatement',
                FunctionDeclaration: 'FunctionDeclaration',
                FunctionExpression: 'FunctionExpression',
                Identifier: 'Identifier',
                IfStatement: 'IfStatement',
                Literal: 'Literal',
                LabeledStatement: 'LabeledStatement',
                LogicalExpression: 'LogicalExpression',
                MemberExpression: 'MemberExpression',
                NewExpression: 'NewExpression',
                ObjectExpression: 'ObjectExpression',
                Program: 'Program',
                Property: 'Property',
                ReturnStatement: 'ReturnStatement',
                SequenceExpression: 'SequenceExpression',
                SwitchStatement: 'SwitchStatement',
                SwitchCase: 'SwitchCase',
                ThisExpression: 'ThisExpression',
                ThrowStatement: 'ThrowStatement',
                TryStatement: 'TryStatement',
                UnaryExpression: 'UnaryExpression',
                UpdateExpression: 'UpdateExpression',
                VariableDeclaration: 'VariableDeclaration',
                VariableDeclarator: 'VariableDeclarator',
                WhileStatement: 'WhileStatement',
                WithStatement: 'WithStatement'
            };

            PropertyKind = {
                Data: 1,
                Get: 2,
                Set: 4
            };

            // Error messages should be identical to V8.
            Messages = {
                UnexpectedToken:  'Unexpected token %0',
                UnexpectedNumber:  'Unexpected number',
                UnexpectedString:  'Unexpected string',
                UnexpectedIdentifier:  'Unexpected identifier',
                UnexpectedReserved:  'Unexpected reserved word',
                UnexpectedEOS:  'Unexpected end of input',
                NewlineAfterThrow:  'Illegal newline after throw',
                InvalidRegExp: 'Invalid regular expression',
                UnterminatedRegExp:  'Invalid regular expression: missing /',
                InvalidLHSInAssignment:  'Invalid left-hand side in assignment',
                InvalidLHSInForIn:  'Invalid left-hand side in for-in',
                MultipleDefaultsInSwitch: 'More than one default clause in switch statement',
                NoCatchOrFinally:  'Missing catch or finally after try',
                UnknownLabel: 'Undefined label \'%0\'',
                Redeclaration: '%0 \'%1\' has already been declared',
                IllegalContinue: 'Illegal continue statement',
                IllegalBreak: 'Illegal break statement',
                IllegalReturn: 'Illegal return statement',
                StrictModeWith:  'Strict mode code may not include a with statement',
                StrictCatchVariable:  'Catch variable may not be eval or arguments in strict mode',
                StrictVarName:  'Variable name may not be eval or arguments in strict mode',
                StrictParamName:  'Parameter name eval or arguments is not allowed in strict mode',
                StrictParamDupe: 'Strict mode function may not have duplicate parameter names',
                StrictFunctionName:  'Function name may not be eval or arguments in strict mode',
                StrictOctalLiteral:  'Octal literals are not allowed in strict mode.',
                StrictDelete:  'Delete of an unqualified identifier in strict mode.',
                StrictDuplicateProperty:  'Duplicate data property in object literal not allowed in strict mode',
                AccessorDataProperty:  'Object literal may not have data and accessor property with the same name',
                AccessorGetSet:  'Object literal may not have multiple get/set accessors with the same name',
                StrictLHSAssignment:  'Assignment to eval or arguments is not allowed in strict mode',
                StrictLHSPostfix:  'Postfix increment/decrement may not have eval or arguments operand in strict mode',
                StrictLHSPrefix:  'Prefix increment/decrement may not have eval or arguments operand in strict mode',
                StrictReservedWord:  'Use of future reserved word in strict mode'
            };

            // See also tools/generate-unicode-regex.py.
            Regex = {
                NonAsciiIdentifierStart: new RegExp('[\xAA\xB5\xBA\xC0-\xD6\xD8-\xF6\xF8-\u02C1\u02C6-\u02D1\u02E0-\u02E4\u02EC\u02EE\u0370-\u0374\u0376\u0377\u037A-\u037D\u0386\u0388-\u038A\u038C\u038E-\u03A1\u03A3-\u03F5\u03F7-\u0481\u048A-\u0527\u0531-\u0556\u0559\u0561-\u0587\u05D0-\u05EA\u05F0-\u05F2\u0620-\u064A\u066E\u066F\u0671-\u06D3\u06D5\u06E5\u06E6\u06EE\u06EF\u06FA-\u06FC\u06FF\u0710\u0712-\u072F\u074D-\u07A5\u07B1\u07CA-\u07EA\u07F4\u07F5\u07FA\u0800-\u0815\u081A\u0824\u0828\u0840-\u0858\u08A0\u08A2-\u08AC\u0904-\u0939\u093D\u0950\u0958-\u0961\u0971-\u0977\u0979-\u097F\u0985-\u098C\u098F\u0990\u0993-\u09A8\u09AA-\u09B0\u09B2\u09B6-\u09B9\u09BD\u09CE\u09DC\u09DD\u09DF-\u09E1\u09F0\u09F1\u0A05-\u0A0A\u0A0F\u0A10\u0A13-\u0A28\u0A2A-\u0A30\u0A32\u0A33\u0A35\u0A36\u0A38\u0A39\u0A59-\u0A5C\u0A5E\u0A72-\u0A74\u0A85-\u0A8D\u0A8F-\u0A91\u0A93-\u0AA8\u0AAA-\u0AB0\u0AB2\u0AB3\u0AB5-\u0AB9\u0ABD\u0AD0\u0AE0\u0AE1\u0B05-\u0B0C\u0B0F\u0B10\u0B13-\u0B28\u0B2A-\u0B30\u0B32\u0B33\u0B35-\u0B39\u0B3D\u0B5C\u0B5D\u0B5F-\u0B61\u0B71\u0B83\u0B85-\u0B8A\u0B8E-\u0B90\u0B92-\u0B95\u0B99\u0B9A\u0B9C\u0B9E\u0B9F\u0BA3\u0BA4\u0BA8-\u0BAA\u0BAE-\u0BB9\u0BD0\u0C05-\u0C0C\u0C0E-\u0C10\u0C12-\u0C28\u0C2A-\u0C33\u0C35-\u0C39\u0C3D\u0C58\u0C59\u0C60\u0C61\u0C85-\u0C8C\u0C8E-\u0C90\u0C92-\u0CA8\u0CAA-\u0CB3\u0CB5-\u0CB9\u0CBD\u0CDE\u0CE0\u0CE1\u0CF1\u0CF2\u0D05-\u0D0C\u0D0E-\u0D10\u0D12-\u0D3A\u0D3D\u0D4E\u0D60\u0D61\u0D7A-\u0D7F\u0D85-\u0D96\u0D9A-\u0DB1\u0DB3-\u0DBB\u0DBD\u0DC0-\u0DC6\u0E01-\u0E30\u0E32\u0E33\u0E40-\u0E46\u0E81\u0E82\u0E84\u0E87\u0E88\u0E8A\u0E8D\u0E94-\u0E97\u0E99-\u0E9F\u0EA1-\u0EA3\u0EA5\u0EA7\u0EAA\u0EAB\u0EAD-\u0EB0\u0EB2\u0EB3\u0EBD\u0EC0-\u0EC4\u0EC6\u0EDC-\u0EDF\u0F00\u0F40-\u0F47\u0F49-\u0F6C\u0F88-\u0F8C\u1000-\u102A\u103F\u1050-\u1055\u105A-\u105D\u1061\u1065\u1066\u106E-\u1070\u1075-\u1081\u108E\u10A0-\u10C5\u10C7\u10CD\u10D0-\u10FA\u10FC-\u1248\u124A-\u124D\u1250-\u1256\u1258\u125A-\u125D\u1260-\u1288\u128A-\u128D\u1290-\u12B0\u12B2-\u12B5\u12B8-\u12BE\u12C0\u12C2-\u12C5\u12C8-\u12D6\u12D8-\u1310\u1312-\u1315\u1318-\u135A\u1380-\u138F\u13A0-\u13F4\u1401-\u166C\u166F-\u167F\u1681-\u169A\u16A0-\u16EA\u16EE-\u16F0\u1700-\u170C\u170E-\u1711\u1720-\u1731\u1740-\u1751\u1760-\u176C\u176E-\u1770\u1780-\u17B3\u17D7\u17DC\u1820-\u1877\u1880-\u18A8\u18AA\u18B0-\u18F5\u1900-\u191C\u1950-\u196D\u1970-\u1974\u1980-\u19AB\u19C1-\u19C7\u1A00-\u1A16\u1A20-\u1A54\u1AA7\u1B05-\u1B33\u1B45-\u1B4B\u1B83-\u1BA0\u1BAE\u1BAF\u1BBA-\u1BE5\u1C00-\u1C23\u1C4D-\u1C4F\u1C5A-\u1C7D\u1CE9-\u1CEC\u1CEE-\u1CF1\u1CF5\u1CF6\u1D00-\u1DBF\u1E00-\u1F15\u1F18-\u1F1D\u1F20-\u1F45\u1F48-\u1F4D\u1F50-\u1F57\u1F59\u1F5B\u1F5D\u1F5F-\u1F7D\u1F80-\u1FB4\u1FB6-\u1FBC\u1FBE\u1FC2-\u1FC4\u1FC6-\u1FCC\u1FD0-\u1FD3\u1FD6-\u1FDB\u1FE0-\u1FEC\u1FF2-\u1FF4\u1FF6-\u1FFC\u2071\u207F\u2090-\u209C\u2102\u2107\u210A-\u2113\u2115\u2119-\u211D\u2124\u2126\u2128\u212A-\u212D\u212F-\u2139\u213C-\u213F\u2145-\u2149\u214E\u2160-\u2188\u2C00-\u2C2E\u2C30-\u2C5E\u2C60-\u2CE4\u2CEB-\u2CEE\u2CF2\u2CF3\u2D00-\u2D25\u2D27\u2D2D\u2D30-\u2D67\u2D6F\u2D80-\u2D96\u2DA0-\u2DA6\u2DA8-\u2DAE\u2DB0-\u2DB6\u2DB8-\u2DBE\u2DC0-\u2DC6\u2DC8-\u2DCE\u2DD0-\u2DD6\u2DD8-\u2DDE\u2E2F\u3005-\u3007\u3021-\u3029\u3031-\u3035\u3038-\u303C\u3041-\u3096\u309D-\u309F\u30A1-\u30FA\u30FC-\u30FF\u3105-\u312D\u3131-\u318E\u31A0-\u31BA\u31F0-\u31FF\u3400-\u4DB5\u4E00-\u9FCC\uA000-\uA48C\uA4D0-\uA4FD\uA500-\uA60C\uA610-\uA61F\uA62A\uA62B\uA640-\uA66E\uA67F-\uA697\uA6A0-\uA6EF\uA717-\uA71F\uA722-\uA788\uA78B-\uA78E\uA790-\uA793\uA7A0-\uA7AA\uA7F8-\uA801\uA803-\uA805\uA807-\uA80A\uA80C-\uA822\uA840-\uA873\uA882-\uA8B3\uA8F2-\uA8F7\uA8FB\uA90A-\uA925\uA930-\uA946\uA960-\uA97C\uA984-\uA9B2\uA9CF\uAA00-\uAA28\uAA40-\uAA42\uAA44-\uAA4B\uAA60-\uAA76\uAA7A\uAA80-\uAAAF\uAAB1\uAAB5\uAAB6\uAAB9-\uAABD\uAAC0\uAAC2\uAADB-\uAADD\uAAE0-\uAAEA\uAAF2-\uAAF4\uAB01-\uAB06\uAB09-\uAB0E\uAB11-\uAB16\uAB20-\uAB26\uAB28-\uAB2E\uABC0-\uABE2\uAC00-\uD7A3\uD7B0-\uD7C6\uD7CB-\uD7FB\uF900-\uFA6D\uFA70-\uFAD9\uFB00-\uFB06\uFB13-\uFB17\uFB1D\uFB1F-\uFB28\uFB2A-\uFB36\uFB38-\uFB3C\uFB3E\uFB40\uFB41\uFB43\uFB44\uFB46-\uFBB1\uFBD3-\uFD3D\uFD50-\uFD8F\uFD92-\uFDC7\uFDF0-\uFDFB\uFE70-\uFE74\uFE76-\uFEFC\uFF21-\uFF3A\uFF41-\uFF5A\uFF66-\uFFBE\uFFC2-\uFFC7\uFFCA-\uFFCF\uFFD2-\uFFD7\uFFDA-\uFFDC]'),
                NonAsciiIdentifierPart: new RegExp('[\xAA\xB5\xBA\xC0-\xD6\xD8-\xF6\xF8-\u02C1\u02C6-\u02D1\u02E0-\u02E4\u02EC\u02EE\u0300-\u0374\u0376\u0377\u037A-\u037D\u0386\u0388-\u038A\u038C\u038E-\u03A1\u03A3-\u03F5\u03F7-\u0481\u0483-\u0487\u048A-\u0527\u0531-\u0556\u0559\u0561-\u0587\u0591-\u05BD\u05BF\u05C1\u05C2\u05C4\u05C5\u05C7\u05D0-\u05EA\u05F0-\u05F2\u0610-\u061A\u0620-\u0669\u066E-\u06D3\u06D5-\u06DC\u06DF-\u06E8\u06EA-\u06FC\u06FF\u0710-\u074A\u074D-\u07B1\u07C0-\u07F5\u07FA\u0800-\u082D\u0840-\u085B\u08A0\u08A2-\u08AC\u08E4-\u08FE\u0900-\u0963\u0966-\u096F\u0971-\u0977\u0979-\u097F\u0981-\u0983\u0985-\u098C\u098F\u0990\u0993-\u09A8\u09AA-\u09B0\u09B2\u09B6-\u09B9\u09BC-\u09C4\u09C7\u09C8\u09CB-\u09CE\u09D7\u09DC\u09DD\u09DF-\u09E3\u09E6-\u09F1\u0A01-\u0A03\u0A05-\u0A0A\u0A0F\u0A10\u0A13-\u0A28\u0A2A-\u0A30\u0A32\u0A33\u0A35\u0A36\u0A38\u0A39\u0A3C\u0A3E-\u0A42\u0A47\u0A48\u0A4B-\u0A4D\u0A51\u0A59-\u0A5C\u0A5E\u0A66-\u0A75\u0A81-\u0A83\u0A85-\u0A8D\u0A8F-\u0A91\u0A93-\u0AA8\u0AAA-\u0AB0\u0AB2\u0AB3\u0AB5-\u0AB9\u0ABC-\u0AC5\u0AC7-\u0AC9\u0ACB-\u0ACD\u0AD0\u0AE0-\u0AE3\u0AE6-\u0AEF\u0B01-\u0B03\u0B05-\u0B0C\u0B0F\u0B10\u0B13-\u0B28\u0B2A-\u0B30\u0B32\u0B33\u0B35-\u0B39\u0B3C-\u0B44\u0B47\u0B48\u0B4B-\u0B4D\u0B56\u0B57\u0B5C\u0B5D\u0B5F-\u0B63\u0B66-\u0B6F\u0B71\u0B82\u0B83\u0B85-\u0B8A\u0B8E-\u0B90\u0B92-\u0B95\u0B99\u0B9A\u0B9C\u0B9E\u0B9F\u0BA3\u0BA4\u0BA8-\u0BAA\u0BAE-\u0BB9\u0BBE-\u0BC2\u0BC6-\u0BC8\u0BCA-\u0BCD\u0BD0\u0BD7\u0BE6-\u0BEF\u0C01-\u0C03\u0C05-\u0C0C\u0C0E-\u0C10\u0C12-\u0C28\u0C2A-\u0C33\u0C35-\u0C39\u0C3D-\u0C44\u0C46-\u0C48\u0C4A-\u0C4D\u0C55\u0C56\u0C58\u0C59\u0C60-\u0C63\u0C66-\u0C6F\u0C82\u0C83\u0C85-\u0C8C\u0C8E-\u0C90\u0C92-\u0CA8\u0CAA-\u0CB3\u0CB5-\u0CB9\u0CBC-\u0CC4\u0CC6-\u0CC8\u0CCA-\u0CCD\u0CD5\u0CD6\u0CDE\u0CE0-\u0CE3\u0CE6-\u0CEF\u0CF1\u0CF2\u0D02\u0D03\u0D05-\u0D0C\u0D0E-\u0D10\u0D12-\u0D3A\u0D3D-\u0D44\u0D46-\u0D48\u0D4A-\u0D4E\u0D57\u0D60-\u0D63\u0D66-\u0D6F\u0D7A-\u0D7F\u0D82\u0D83\u0D85-\u0D96\u0D9A-\u0DB1\u0DB3-\u0DBB\u0DBD\u0DC0-\u0DC6\u0DCA\u0DCF-\u0DD4\u0DD6\u0DD8-\u0DDF\u0DF2\u0DF3\u0E01-\u0E3A\u0E40-\u0E4E\u0E50-\u0E59\u0E81\u0E82\u0E84\u0E87\u0E88\u0E8A\u0E8D\u0E94-\u0E97\u0E99-\u0E9F\u0EA1-\u0EA3\u0EA5\u0EA7\u0EAA\u0EAB\u0EAD-\u0EB9\u0EBB-\u0EBD\u0EC0-\u0EC4\u0EC6\u0EC8-\u0ECD\u0ED0-\u0ED9\u0EDC-\u0EDF\u0F00\u0F18\u0F19\u0F20-\u0F29\u0F35\u0F37\u0F39\u0F3E-\u0F47\u0F49-\u0F6C\u0F71-\u0F84\u0F86-\u0F97\u0F99-\u0FBC\u0FC6\u1000-\u1049\u1050-\u109D\u10A0-\u10C5\u10C7\u10CD\u10D0-\u10FA\u10FC-\u1248\u124A-\u124D\u1250-\u1256\u1258\u125A-\u125D\u1260-\u1288\u128A-\u128D\u1290-\u12B0\u12B2-\u12B5\u12B8-\u12BE\u12C0\u12C2-\u12C5\u12C8-\u12D6\u12D8-\u1310\u1312-\u1315\u1318-\u135A\u135D-\u135F\u1380-\u138F\u13A0-\u13F4\u1401-\u166C\u166F-\u167F\u1681-\u169A\u16A0-\u16EA\u16EE-\u16F0\u1700-\u170C\u170E-\u1714\u1720-\u1734\u1740-\u1753\u1760-\u176C\u176E-\u1770\u1772\u1773\u1780-\u17D3\u17D7\u17DC\u17DD\u17E0-\u17E9\u180B-\u180D\u1810-\u1819\u1820-\u1877\u1880-\u18AA\u18B0-\u18F5\u1900-\u191C\u1920-\u192B\u1930-\u193B\u1946-\u196D\u1970-\u1974\u1980-\u19AB\u19B0-\u19C9\u19D0-\u19D9\u1A00-\u1A1B\u1A20-\u1A5E\u1A60-\u1A7C\u1A7F-\u1A89\u1A90-\u1A99\u1AA7\u1B00-\u1B4B\u1B50-\u1B59\u1B6B-\u1B73\u1B80-\u1BF3\u1C00-\u1C37\u1C40-\u1C49\u1C4D-\u1C7D\u1CD0-\u1CD2\u1CD4-\u1CF6\u1D00-\u1DE6\u1DFC-\u1F15\u1F18-\u1F1D\u1F20-\u1F45\u1F48-\u1F4D\u1F50-\u1F57\u1F59\u1F5B\u1F5D\u1F5F-\u1F7D\u1F80-\u1FB4\u1FB6-\u1FBC\u1FBE\u1FC2-\u1FC4\u1FC6-\u1FCC\u1FD0-\u1FD3\u1FD6-\u1FDB\u1FE0-\u1FEC\u1FF2-\u1FF4\u1FF6-\u1FFC\u200C\u200D\u203F\u2040\u2054\u2071\u207F\u2090-\u209C\u20D0-\u20DC\u20E1\u20E5-\u20F0\u2102\u2107\u210A-\u2113\u2115\u2119-\u211D\u2124\u2126\u2128\u212A-\u212D\u212F-\u2139\u213C-\u213F\u2145-\u2149\u214E\u2160-\u2188\u2C00-\u2C2E\u2C30-\u2C5E\u2C60-\u2CE4\u2CEB-\u2CF3\u2D00-\u2D25\u2D27\u2D2D\u2D30-\u2D67\u2D6F\u2D7F-\u2D96\u2DA0-\u2DA6\u2DA8-\u2DAE\u2DB0-\u2DB6\u2DB8-\u2DBE\u2DC0-\u2DC6\u2DC8-\u2DCE\u2DD0-\u2DD6\u2DD8-\u2DDE\u2DE0-\u2DFF\u2E2F\u3005-\u3007\u3021-\u302F\u3031-\u3035\u3038-\u303C\u3041-\u3096\u3099\u309A\u309D-\u309F\u30A1-\u30FA\u30FC-\u30FF\u3105-\u312D\u3131-\u318E\u31A0-\u31BA\u31F0-\u31FF\u3400-\u4DB5\u4E00-\u9FCC\uA000-\uA48C\uA4D0-\uA4FD\uA500-\uA60C\uA610-\uA62B\uA640-\uA66F\uA674-\uA67D\uA67F-\uA697\uA69F-\uA6F1\uA717-\uA71F\uA722-\uA788\uA78B-\uA78E\uA790-\uA793\uA7A0-\uA7AA\uA7F8-\uA827\uA840-\uA873\uA880-\uA8C4\uA8D0-\uA8D9\uA8E0-\uA8F7\uA8FB\uA900-\uA92D\uA930-\uA953\uA960-\uA97C\uA980-\uA9C0\uA9CF-\uA9D9\uAA00-\uAA36\uAA40-\uAA4D\uAA50-\uAA59\uAA60-\uAA76\uAA7A\uAA7B\uAA80-\uAAC2\uAADB-\uAADD\uAAE0-\uAAEF\uAAF2-\uAAF6\uAB01-\uAB06\uAB09-\uAB0E\uAB11-\uAB16\uAB20-\uAB26\uAB28-\uAB2E\uABC0-\uABEA\uABEC\uABED\uABF0-\uABF9\uAC00-\uD7A3\uD7B0-\uD7C6\uD7CB-\uD7FB\uF900-\uFA6D\uFA70-\uFAD9\uFB00-\uFB06\uFB13-\uFB17\uFB1D-\uFB28\uFB2A-\uFB36\uFB38-\uFB3C\uFB3E\uFB40\uFB41\uFB43\uFB44\uFB46-\uFBB1\uFBD3-\uFD3D\uFD50-\uFD8F\uFD92-\uFDC7\uFDF0-\uFDFB\uFE00-\uFE0F\uFE20-\uFE26\uFE33\uFE34\uFE4D-\uFE4F\uFE70-\uFE74\uFE76-\uFEFC\uFF10-\uFF19\uFF21-\uFF3A\uFF3F\uFF41-\uFF5A\uFF66-\uFFBE\uFFC2-\uFFC7\uFFCA-\uFFCF\uFFD2-\uFFD7\uFFDA-\uFFDC]')
            };

            // Ensure the condition is true, otherwise throw an error.
            // This is only to have a better contract semantic, i.e. another safety net
            // to catch a logic error. The condition shall be fulfilled in normal case.
            // Do NOT use this to enforce a certain condition on any user input.

            function assert(condition, message) {
                /* istanbul ignore if */
                if (!condition) {
                    throw new Error('ASSERT: ' + message);
                }
            }

            function isDecimalDigit(ch) {
                return (ch >= 48 && ch <= 57);   // 0..9
            }

            function isHexDigit(ch) {
                return '0123456789abcdefABCDEF'.indexOf(ch) >= 0;
            }

            function isOctalDigit(ch) {
                return '01234567'.indexOf(ch) >= 0;
            }


            // 7.2 White Space

            function isWhiteSpace(ch) {
                return (ch === 0x20) || (ch === 0x09) || (ch === 0x0B) || (ch === 0x0C) || (ch === 0xA0) ||
                    (ch >= 0x1680 && [0x1680, 0x180E, 0x2000, 0x2001, 0x2002, 0x2003, 0x2004, 0x2005, 0x2006, 0x2007, 0x2008, 0x2009, 0x200A, 0x202F, 0x205F, 0x3000, 0xFEFF].indexOf(ch) >= 0);
            }

            // 7.3 Line Terminators

            function isLineTerminator(ch) {
                return (ch === 0x0A) || (ch === 0x0D) || (ch === 0x2028) || (ch === 0x2029);
            }

            // 7.6 Identifier Names and Identifiers

            function isIdentifierStart(ch) {
                return (ch === 0x24) || (ch === 0x5F) ||  // $ (dollar) and _ (underscore)
                    (ch >= 0x41 && ch <= 0x5A) ||         // A..Z
                    (ch >= 0x61 && ch <= 0x7A) ||         // a..z
                    (ch === 0x5C) ||                      // \ (backslash)
                    ((ch >= 0x80) && Regex.NonAsciiIdentifierStart.test(String.fromCharCode(ch)));
            }

            function isIdentifierPart(ch) {
                return (ch === 0x24) || (ch === 0x5F) ||  // $ (dollar) and _ (underscore)
                    (ch >= 0x41 && ch <= 0x5A) ||         // A..Z
                    (ch >= 0x61 && ch <= 0x7A) ||         // a..z
                    (ch >= 0x30 && ch <= 0x39) ||         // 0..9
                    (ch === 0x5C) ||                      // \ (backslash)
                    ((ch >= 0x80) && Regex.NonAsciiIdentifierPart.test(String.fromCharCode(ch)));
            }

            // 7.6.1.2 Future Reserved Words

            function isFutureReservedWord(id) {
                switch (id) {
                    case 'class':
                    case 'enum':
                    case 'export':
                    case 'extends':
                    case 'import':
                    case 'super':
                        return true;
                    default:
                        return false;
                }
            }

            function isStrictModeReservedWord(id) {
                switch (id) {
                    case 'implements':
                    case 'interface':
                    case 'package':
                    case 'private':
                    case 'protected':
                    case 'public':
                    case 'static':
                    case 'yield':
                    case 'let':
                        return true;
                    default:
                        return false;
                }
            }

            function isRestrictedWord(id) {
                return id === 'eval' || id === 'arguments';
            }

            // 7.6.1.1 Keywords

            function isKeyword(id) {
                if (strict && isStrictModeReservedWord(id)) {
                    return true;
                }

                // 'const' is specialized as Keyword in V8.
                // 'yield' and 'let' are for compatiblity with SpiderMonkey and ES.next.
                // Some others are from future reserved words.

                switch (id.length) {
                    case 2:
                        return (id === 'if') || (id === 'in') || (id === 'do');
                    case 3:
                        return (id === 'var') || (id === 'for') || (id === 'new') ||
                            (id === 'try') || (id === 'let');
                    case 4:
                        return (id === 'this') || (id === 'else') || (id === 'case') ||
                            (id === 'void') || (id === 'with') || (id === 'enum');
                    case 5:
                        return (id === 'while') || (id === 'break') || (id === 'catch') ||
                            (id === 'throw') || (id === 'const') || (id === 'yield') ||
                            (id === 'class') || (id === 'super');
                    case 6:
                        return (id === 'return') || (id === 'typeof') || (id === 'delete') ||
                            (id === 'switch') || (id === 'export') || (id === 'import');
                    case 7:
                        return (id === 'default') || (id === 'finally') || (id === 'extends');
                    case 8:
                        return (id === 'function') || (id === 'continue') || (id === 'debugger');
                    case 10:
                        return (id === 'instanceof');
                    default:
                        return false;
                }
            }

            // 7.4 Comments

            function addComment(type, value, start, end, loc) {
                var comment, attacher;

                assert(typeof start === 'number', 'Comment must have valid position');

                // Because the way the actual token is scanned, often the comments
                // (if any) are skipped twice during the lexical analysis.
                // Thus, we need to skip adding a comment if the comment array already
                // handled it.
                if (state.lastCommentStart >= start) {
                    return;
                }
                state.lastCommentStart = start;

                comment = {
                    type: type,
                    value: value
                };
                if (extra.range) {
                    comment.range = [start, end];
                }
                if (extra.loc) {
                    comment.loc = loc;
                }
                extra.comments.push(comment);
                if (extra.attachComment) {
                    extra.leadingComments.push(comment);
                    extra.trailingComments.push(comment);
                }
            }

            function skipSingleLineComment(offset) {
                var start, loc, ch, comment;

                start = index - offset;
                loc = {
                    start: {
                        line: lineNumber,
                        column: index - lineStart - offset
                    }
                };

                while (index < length) {
                    ch = source.charCodeAt(index);
                    ++index;
                    if (isLineTerminator(ch)) {
                        if (extra.comments) {
                            comment = source.slice(start + offset, index - 1);
                            loc.end = {
                                line: lineNumber,
                                column: index - lineStart - 1
                            };
                            addComment('Line', comment, start, index - 1, loc);
                        }
                        if (ch === 13 && source.charCodeAt(index) === 10) {
                            ++index;
                        }
                        ++lineNumber;
                        lineStart = index;
                        return;
                    }
                }

                if (extra.comments) {
                    comment = source.slice(start + offset, index);
                    loc.end = {
                        line: lineNumber,
                        column: index - lineStart
                    };
                    addComment('Line', comment, start, index, loc);
                }
            }

            function skipMultiLineComment() {
                var start, loc, ch, comment;

                if (extra.comments) {
                    start = index - 2;
                    loc = {
                        start: {
                            line: lineNumber,
                            column: index - lineStart - 2
                        }
                    };
                }

                while (index < length) {
                    ch = source.charCodeAt(index);
                    if (isLineTerminator(ch)) {
                        if (ch === 0x0D && source.charCodeAt(index + 1) === 0x0A) {
                            ++index;
                        }
                        ++lineNumber;
                        ++index;
                        lineStart = index;
                        if (index >= length) {
                            throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
                        }
                    } else if (ch === 0x2A) {
                        // Block comment ends with '*/'.
                        if (source.charCodeAt(index + 1) === 0x2F) {
                            ++index;
                            ++index;
                            if (extra.comments) {
                                comment = source.slice(start + 2, index - 2);
                                loc.end = {
                                    line: lineNumber,
                                    column: index - lineStart
                                };
                                addComment('Block', comment, start, index, loc);
                            }
                            return;
                        }
                        ++index;
                    } else {
                        ++index;
                    }
                }

                throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
            }

            function skipComment() {
                var ch, start;

                start = (index === 0);
                while (index < length) {
                    ch = source.charCodeAt(index);

                    if (isWhiteSpace(ch)) {
                        ++index;
                    } else if (isLineTerminator(ch)) {
                        ++index;
                        if (ch === 0x0D && source.charCodeAt(index) === 0x0A) {
                            ++index;
                        }
                        ++lineNumber;
                        lineStart = index;
                        start = true;
                    } else if (ch === 0x2F) { // U+002F is '/'
                        ch = source.charCodeAt(index + 1);
                        if (ch === 0x2F) {
                            ++index;
                            ++index;
                            skipSingleLineComment(2);
                            start = true;
                        } else if (ch === 0x2A) {  // U+002A is '*'
                            ++index;
                            ++index;
                            skipMultiLineComment();
                        } else {
                            break;
                        }
                    } else if (start && ch === 0x2D) { // U+002D is '-'
                        // U+003E is '>'
                        if ((source.charCodeAt(index + 1) === 0x2D) && (source.charCodeAt(index + 2) === 0x3E)) {
                            // '-->' is a single-line comment
                            index += 3;
                            skipSingleLineComment(3);
                        } else {
                            break;
                        }
                    } else if (ch === 0x3C) { // U+003C is '<'
                        if (source.slice(index + 1, index + 4) === '!--') {
                            ++index; // `<`
                            ++index; // `!`
                            ++index; // `-`
                            ++index; // `-`
                            skipSingleLineComment(4);
                        } else {
                            break;
                        }
                    } else {
                        break;
                    }
                }
            }

            function scanHexEscape(prefix) {
                var i, len, ch, code = 0;

                len = (prefix === 'u') ? 4 : 2;
                for (i = 0; i < len; ++i) {
                    if (index < length && isHexDigit(source[index])) {
                        ch = source[index++];
                        code = code * 16 + '0123456789abcdef'.indexOf(ch.toLowerCase());
                    } else {
                        return '';
                    }
                }
                return String.fromCharCode(code);
            }

            function getEscapedIdentifier() {
                var ch, id;

                ch = source.charCodeAt(index++);
                id = String.fromCharCode(ch);

                // '\u' (U+005C, U+0075) denotes an escaped character.
                if (ch === 0x5C) {
                    if (source.charCodeAt(index) !== 0x75) {
                        throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
                    }
                    ++index;
                    ch = scanHexEscape('u');
                    if (!ch || ch === '\\' || !isIdentifierStart(ch.charCodeAt(0))) {
                        throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
                    }
                    id = ch;
                }

                while (index < length) {
                    ch = source.charCodeAt(index);
                    if (!isIdentifierPart(ch)) {
                        break;
                    }
                    ++index;
                    id += String.fromCharCode(ch);

                    // '\u' (U+005C, U+0075) denotes an escaped character.
                    if (ch === 0x5C) {
                        id = id.substr(0, id.length - 1);
                        if (source.charCodeAt(index) !== 0x75) {
                            throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
                        }
                        ++index;
                        ch = scanHexEscape('u');
                        if (!ch || ch === '\\' || !isIdentifierPart(ch.charCodeAt(0))) {
                            throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
                        }
                        id += ch;
                    }
                }

                return id;
            }

            function getIdentifier() {
                var start, ch;

                start = index++;
                while (index < length) {
                    ch = source.charCodeAt(index);
                    if (ch === 0x5C) {
                        // Blackslash (U+005C) marks Unicode escape sequence.
                        index = start;
                        return getEscapedIdentifier();
                    }
                    if (isIdentifierPart(ch)) {
                        ++index;
                    } else {
                        break;
                    }
                }

                return source.slice(start, index);
            }

            function scanIdentifier() {
                var start, id, type;

                start = index;

                // Backslash (U+005C) starts an escaped character.
                id = (source.charCodeAt(index) === 0x5C) ? getEscapedIdentifier() : getIdentifier();

                // There is no keyword or literal with only one character.
                // Thus, it must be an identifier.
                if (id.length === 1) {
                    type = Token.Identifier;
                } else if (isKeyword(id)) {
                    type = Token.Keyword;
                } else if (id === 'null') {
                    type = Token.NullLiteral;
                } else if (id === 'true' || id === 'false') {
                    type = Token.BooleanLiteral;
                } else {
                    type = Token.Identifier;
                }

                return {
                    type: type,
                    value: id,
                    lineNumber: lineNumber,
                    lineStart: lineStart,
                    start: start,
                    end: index
                };
            }


            // 7.7 Punctuators

            function scanPunctuator() {
                var start = index,
                    code = source.charCodeAt(index),
                    code2,
                    ch1 = source[index],
                    ch2,
                    ch3,
                    ch4;

                switch (code) {

                    // Check for most common single-character punctuators.
                    case 0x2E:  // . dot
                    case 0x28:  // ( open bracket
                    case 0x29:  // ) close bracket
                    case 0x3B:  // ; semicolon
                    case 0x2C:  // , comma
                    case 0x7B:  // { open curly brace
                    case 0x7D:  // } close curly brace
                    case 0x5B:  // [
                    case 0x5D:  // ]
                    case 0x3A:  // :
                    case 0x3F:  // ?
                    case 0x7E:  // ~
                        ++index;
                        if (extra.tokenize) {
                            if (code === 0x28) {
                                extra.openParenToken = extra.tokens.length;
                            } else if (code === 0x7B) {
                                extra.openCurlyToken = extra.tokens.length;
                            }
                        }
                        return {
                            type: Token.Punctuator,
                            value: String.fromCharCode(code),
                            lineNumber: lineNumber,
                            lineStart: lineStart,
                            start: start,
                            end: index
                        };

                    default:
                        code2 = source.charCodeAt(index + 1);

                        // '=' (U+003D) marks an assignment or comparison operator.
                        if (code2 === 0x3D) {
                            switch (code) {
                                case 0x2B:  // +
                                case 0x2D:  // -
                                case 0x2F:  // /
                                case 0x3C:  // <
                                case 0x3E:  // >
                                case 0x5E:  // ^
                                case 0x7C:  // |
                                case 0x25:  // %
                                case 0x26:  // &
                                case 0x2A:  // *
                                    index += 2;
                                    return {
                                        type: Token.Punctuator,
                                        value: String.fromCharCode(code) + String.fromCharCode(code2),
                                        lineNumber: lineNumber,
                                        lineStart: lineStart,
                                        start: start,
                                        end: index
                                    };

                                case 0x21: // !
                                case 0x3D: // =
                                    index += 2;

                                    // !== and ===
                                    if (source.charCodeAt(index) === 0x3D) {
                                        ++index;
                                    }
                                    return {
                                        type: Token.Punctuator,
                                        value: source.slice(start, index),
                                        lineNumber: lineNumber,
                                        lineStart: lineStart,
                                        start: start,
                                        end: index
                                    };
                            }
                        }
                }

                // 4-character punctuator: >>>=

                ch4 = source.substr(index, 4);

                if (ch4 === '>>>=') {
                    index += 4;
                    return {
                        type: Token.Punctuator,
                        value: ch4,
                        lineNumber: lineNumber,
                        lineStart: lineStart,
                        start: start,
                        end: index
                    };
                }

                // 3-character punctuators: === !== >>> <<= >>=

                ch3 = ch4.substr(0, 3);

                if (ch3 === '>>>' || ch3 === '<<=' || ch3 === '>>=') {
                    index += 3;
                    return {
                        type: Token.Punctuator,
                        value: ch3,
                        lineNumber: lineNumber,
                        lineStart: lineStart,
                        start: start,
                        end: index
                    };
                }

                // Other 2-character punctuators: ++ -- << >> && ||
                ch2 = ch3.substr(0, 2);

                if ((ch1 === ch2[1] && ('+-<>&|'.indexOf(ch1) >= 0)) || ch2 === '=>') {
                    index += 2;
                    return {
                        type: Token.Punctuator,
                        value: ch2,
                        lineNumber: lineNumber,
                        lineStart: lineStart,
                        start: start,
                        end: index
                    };
                }

                // 1-character punctuators: < > = ! + - * % & | ^ /
                if ('<>=!+-*%&|^/'.indexOf(ch1) >= 0) {
                    ++index;
                    return {
                        type: Token.Punctuator,
                        value: ch1,
                        lineNumber: lineNumber,
                        lineStart: lineStart,
                        start: start,
                        end: index
                    };
                }

                throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
            }

            // 7.8.3 Numeric Literals

            function scanHexLiteral(start) {
                var number = '';

                while (index < length) {
                    if (!isHexDigit(source[index])) {
                        break;
                    }
                    number += source[index++];
                }

                if (number.length === 0) {
                    throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
                }

                if (isIdentifierStart(source.charCodeAt(index))) {
                    throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
                }

                return {
                    type: Token.NumericLiteral,
                    value: parseInt('0x' + number, 16),
                    lineNumber: lineNumber,
                    lineStart: lineStart,
                    start: start,
                    end: index
                };
            }

            function scanOctalLiteral(start) {
                var number = '0' + source[index++];
                while (index < length) {
                    if (!isOctalDigit(source[index])) {
                        break;
                    }
                    number += source[index++];
                }

                if (isIdentifierStart(source.charCodeAt(index)) || isDecimalDigit(source.charCodeAt(index))) {
                    throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
                }

                return {
                    type: Token.NumericLiteral,
                    value: parseInt(number, 8),
                    octal: true,
                    lineNumber: lineNumber,
                    lineStart: lineStart,
                    start: start,
                    end: index
                };
            }

            function scanNumericLiteral() {
                var number, start, ch;

                ch = source[index];
                assert(isDecimalDigit(ch.charCodeAt(0)) || (ch === '.'),
                    'Numeric literal must start with a decimal digit or a decimal point');

                start = index;
                number = '';
                if (ch !== '.') {
                    number = source[index++];
                    ch = source[index];

                    // Hex number starts with '0x'.
                    // Octal number starts with '0'.
                    if (number === '0') {
                        if (ch === 'x' || ch === 'X') {
                            ++index;
                            return scanHexLiteral(start);
                        }
                        if (isOctalDigit(ch)) {
                            return scanOctalLiteral(start);
                        }

                        // decimal number starts with '0' such as '09' is illegal.
                        if (ch && isDecimalDigit(ch.charCodeAt(0))) {
                            throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
                        }
                    }

                    while (isDecimalDigit(source.charCodeAt(index))) {
                        number += source[index++];
                    }
                    ch = source[index];
                }

                if (ch === '.') {
                    number += source[index++];
                    while (isDecimalDigit(source.charCodeAt(index))) {
                        number += source[index++];
                    }
                    ch = source[index];
                }

                if (ch === 'e' || ch === 'E') {
                    number += source[index++];

                    ch = source[index];
                    if (ch === '+' || ch === '-') {
                        number += source[index++];
                    }
                    if (isDecimalDigit(source.charCodeAt(index))) {
                        while (isDecimalDigit(source.charCodeAt(index))) {
                            number += source[index++];
                        }
                    } else {
                        throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
                    }
                }

                if (isIdentifierStart(source.charCodeAt(index))) {
                    throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
                }

                return {
                    type: Token.NumericLiteral,
                    value: parseFloat(number),
                    lineNumber: lineNumber,
                    lineStart: lineStart,
                    start: start,
                    end: index
                };
            }

            // 7.8.4 String Literals

            function scanStringLiteral() {
                var str = '', quote, start, ch, code, unescaped, restore, octal = false, startLineNumber, startLineStart;
                startLineNumber = lineNumber;
                startLineStart = lineStart;

                quote = source[index];
                assert((quote === '\'' || quote === '"'),
                    'String literal must starts with a quote');

                start = index;
                ++index;

                while (index < length) {
                    ch = source[index++];

                    if (ch === quote) {
                        quote = '';
                        break;
                    } else if (ch === '\\') {
                        ch = source[index++];
                        if (!ch || !isLineTerminator(ch.charCodeAt(0))) {
                            switch (ch) {
                                case 'u':
                                case 'x':
                                    restore = index;
                                    unescaped = scanHexEscape(ch);
                                    if (unescaped) {
                                        str += unescaped;
                                    } else {
                                        index = restore;
                                        str += ch;
                                    }
                                    break;
                                case 'n':
                                    str += '\n';
                                    break;
                                case 'r':
                                    str += '\r';
                                    break;
                                case 't':
                                    str += '\t';
                                    break;
                                case 'b':
                                    str += '\b';
                                    break;
                                case 'f':
                                    str += '\f';
                                    break;
                                case 'v':
                                    str += '\x0B';
                                    break;

                                default:
                                    if (isOctalDigit(ch)) {
                                        code = '01234567'.indexOf(ch);

                                        // \0 is not octal escape sequence
                                        if (code !== 0) {
                                            octal = true;
                                        }

                                        if (index < length && isOctalDigit(source[index])) {
                                            octal = true;
                                            code = code * 8 + '01234567'.indexOf(source[index++]);

                                            // 3 digits are only allowed when string starts
                                            // with 0, 1, 2, 3
                                            if ('0123'.indexOf(ch) >= 0 &&
                                                index < length &&
                                                isOctalDigit(source[index])) {
                                                code = code * 8 + '01234567'.indexOf(source[index++]);
                                            }
                                        }
                                        str += String.fromCharCode(code);
                                    } else {
                                        str += ch;
                                    }
                                    break;
                            }
                        } else {
                            ++lineNumber;
                            if (ch ===  '\r' && source[index] === '\n') {
                                ++index;
                            }
                            lineStart = index;
                        }
                    } else if (isLineTerminator(ch.charCodeAt(0))) {
                        break;
                    } else {
                        str += ch;
                    }
                }

                if (quote !== '') {
                    throwError({}, Messages.UnexpectedToken, 'ILLEGAL');
                }

                return {
                    type: Token.StringLiteral,
                    value: str,
                    octal: octal,
                    startLineNumber: startLineNumber,
                    startLineStart: startLineStart,
                    lineNumber: lineNumber,
                    lineStart: lineStart,
                    start: start,
                    end: index
                };
            }

            function testRegExp(pattern, flags) {
                var value;
                try {
                    value = new RegExp(pattern, flags);
                } catch (e) {
                    throwError({}, Messages.InvalidRegExp);
                }
                return value;
            }

            function scanRegExpBody() {
                var ch, str, classMarker, terminated, body;

                ch = source[index];
                assert(ch === '/', 'Regular expression literal must start with a slash');
                str = source[index++];

                classMarker = false;
                terminated = false;
                while (index < length) {
                    ch = source[index++];
                    str += ch;
                    if (ch === '\\') {
                        ch = source[index++];
                        // ECMA-262 7.8.5
                        if (isLineTerminator(ch.charCodeAt(0))) {
                            throwError({}, Messages.UnterminatedRegExp);
                        }
                        str += ch;
                    } else if (isLineTerminator(ch.charCodeAt(0))) {
                        throwError({}, Messages.UnterminatedRegExp);
                    } else if (classMarker) {
                        if (ch === ']') {
                            classMarker = false;
                        }
                    } else {
                        if (ch === '/') {
                            terminated = true;
                            break;
                        } else if (ch === '[') {
                            classMarker = true;
                        }
                    }
                }

                if (!terminated) {
                    throwError({}, Messages.UnterminatedRegExp);
                }

                // Exclude leading and trailing slash.
                body = str.substr(1, str.length - 2);
                return {
                    value: body,
                    literal: str
                };
            }

            function scanRegExpFlags() {
                var ch, str, flags, restore;

                str = '';
                flags = '';
                while (index < length) {
                    ch = source[index];
                    if (!isIdentifierPart(ch.charCodeAt(0))) {
                        break;
                    }

                    ++index;
                    if (ch === '\\' && index < length) {
                        ch = source[index];
                        if (ch === 'u') {
                            ++index;
                            restore = index;
                            ch = scanHexEscape('u');
                            if (ch) {
                                flags += ch;
                                for (str += '\\u'; restore < index; ++restore) {
                                    str += source[restore];
                                }
                            } else {
                                index = restore;
                                flags += 'u';
                                str += '\\u';
                            }
                            throwErrorTolerant({}, Messages.UnexpectedToken, 'ILLEGAL');
                        } else {
                            str += '\\';
                            throwErrorTolerant({}, Messages.UnexpectedToken, 'ILLEGAL');
                        }
                    } else {
                        flags += ch;
                        str += ch;
                    }
                }

                return {
                    value: flags,
                    literal: str
                };
            }

            function scanRegExp() {
                var start, body, flags, pattern, value;

                lookahead = null;
                skipComment();
                start = index;

                body = scanRegExpBody();
                flags = scanRegExpFlags();
                value = testRegExp(body.value, flags.value);

                if (extra.tokenize) {
                    return {
                        type: Token.RegularExpression,
                        value: value,
                        lineNumber: lineNumber,
                        lineStart: lineStart,
                        start: start,
                        end: index
                    };
                }

                return {
                    literal: body.literal + flags.literal,
                    value: value,
                    start: start,
                    end: index
                };
            }

            function collectRegex() {
                var pos, loc, regex, token;

                skipComment();

                pos = index;
                loc = {
                    start: {
                        line: lineNumber,
                        column: index - lineStart
                    }
                };

                regex = scanRegExp();
                loc.end = {
                    line: lineNumber,
                    column: index - lineStart
                };

                /* istanbul ignore next */
                if (!extra.tokenize) {
                    // Pop the previous token, which is likely '/' or '/='
                    if (extra.tokens.length > 0) {
                        token = extra.tokens[extra.tokens.length - 1];
                        if (token.range[0] === pos && token.type === 'Punctuator') {
                            if (token.value === '/' || token.value === '/=') {
                                extra.tokens.pop();
                            }
                        }
                    }

                    extra.tokens.push({
                        type: 'RegularExpression',
                        value: regex.literal,
                        range: [pos, index],
                        loc: loc
                    });
                }

                return regex;
            }

            function isIdentifierName(token) {
                return token.type === Token.Identifier ||
                    token.type === Token.Keyword ||
                    token.type === Token.BooleanLiteral ||
                    token.type === Token.NullLiteral;
            }

            function advanceSlash() {
                var prevToken,
                    checkToken;
                // Using the following algorithm:
                // https://github.com/mozilla/sweet.js/wiki/design
                prevToken = extra.tokens[extra.tokens.length - 1];
                if (!prevToken) {
                    // Nothing before that: it cannot be a division.
                    return collectRegex();
                }
                if (prevToken.type === 'Punctuator') {
                    if (prevToken.value === ']') {
                        return scanPunctuator();
                    }
                    if (prevToken.value === ')') {
                        checkToken = extra.tokens[extra.openParenToken - 1];
                        if (checkToken &&
                            checkToken.type === 'Keyword' &&
                            (checkToken.value === 'if' ||
                                checkToken.value === 'while' ||
                                checkToken.value === 'for' ||
                                checkToken.value === 'with')) {
                            return collectRegex();
                        }
                        return scanPunctuator();
                    }
                    if (prevToken.value === '}') {
                        // Dividing a function by anything makes little sense,
                        // but we have to check for that.
                        if (extra.tokens[extra.openCurlyToken - 3] &&
                            extra.tokens[extra.openCurlyToken - 3].type === 'Keyword') {
                            // Anonymous function.
                            checkToken = extra.tokens[extra.openCurlyToken - 4];
                            if (!checkToken) {
                                return scanPunctuator();
                            }
                        } else if (extra.tokens[extra.openCurlyToken - 4] &&
                            extra.tokens[extra.openCurlyToken - 4].type === 'Keyword') {
                            // Named function.
                            checkToken = extra.tokens[extra.openCurlyToken - 5];
                            if (!checkToken) {
                                return collectRegex();
                            }
                        } else {
                            return scanPunctuator();
                        }
                        // checkToken determines whether the function is
                        // a declaration or an expression.
                        if (FnExprTokens.indexOf(checkToken.value) >= 0) {
                            // It is an expression.
                            return scanPunctuator();
                        }
                        // It is a declaration.
                        return collectRegex();
                    }
                    return collectRegex();
                }
                if (prevToken.type === 'Keyword') {
                    return collectRegex();
                }
                return scanPunctuator();
            }

            function advance() {
                var ch;

                skipComment();

                if (index >= length) {
                    return {
                        type: Token.EOF,
                        lineNumber: lineNumber,
                        lineStart: lineStart,
                        start: index,
                        end: index
                    };
                }

                ch = source.charCodeAt(index);

                if (isIdentifierStart(ch)) {
                    return scanIdentifier();
                }

                // Very common: ( and ) and ;
                if (ch === 0x28 || ch === 0x29 || ch === 0x3B) {
                    return scanPunctuator();
                }

                // String literal starts with single quote (U+0027) or double quote (U+0022).
                if (ch === 0x27 || ch === 0x22) {
                    return scanStringLiteral();
                }


                // Dot (.) U+002E can also start a floating-point number, hence the need
                // to check the next character.
                if (ch === 0x2E) {
                    if (isDecimalDigit(source.charCodeAt(index + 1))) {
                        return scanNumericLiteral();
                    }
                    return scanPunctuator();
                }

                if (isDecimalDigit(ch)) {
                    return scanNumericLiteral();
                }

                // Slash (/) U+002F can also start a regex.
                if (extra.tokenize && ch === 0x2F) {
                    return advanceSlash();
                }

                return scanPunctuator();
            }

            function collectToken() {
                var loc, token, range, value;

                skipComment();
                loc = {
                    start: {
                        line: lineNumber,
                        column: index - lineStart
                    }
                };

                token = advance();
                loc.end = {
                    line: lineNumber,
                    column: index - lineStart
                };

                if (token.type !== Token.EOF) {
                    value = source.slice(token.start, token.end);
                    extra.tokens.push({
                        type: TokenName[token.type],
                        value: value,
                        range: [token.start, token.end],
                        loc: loc
                    });
                }

                return token;
            }

            function lex() {
                var token;

                token = lookahead;
                index = token.end;
                lineNumber = token.lineNumber;
                lineStart = token.lineStart;

                lookahead = (typeof extra.tokens !== 'undefined') ? collectToken() : advance();

                index = token.end;
                lineNumber = token.lineNumber;
                lineStart = token.lineStart;

                return token;
            }

            function peek() {
                var pos, line, start;

                pos = index;
                line = lineNumber;
                start = lineStart;
                lookahead = (typeof extra.tokens !== 'undefined') ? collectToken() : advance();
                index = pos;
                lineNumber = line;
                lineStart = start;
            }

            function Position(line, column) {
                this.line = line;
                this.column = column;
            }

            function SourceLocation(startLine, startColumn, line, column) {
                this.start = new Posi
