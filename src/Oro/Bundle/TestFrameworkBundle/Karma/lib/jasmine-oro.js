(function(j$) {
    'use strict';

    /**
     * Combines an object with multiple {@link Spy}s or {@link jasmine.createSpyObj}s as its members.
     * @name jasmine.combineSpyObj
     * @function
     * @param {String} [baseName] - Base name for the spies in the object.
     * @param {Array.<Function|Object>} props - Array of spy functions or spy objects.
     * @return {Object}
     */
    j$.combineSpyObj = function(baseName, props) {
        var baseNameIsCollection = j$.isObject_(baseName) || j$.isArray_(baseName);

        if (baseNameIsCollection && j$.util.isUndefined(props)) {
            props = baseName;
            baseName = 'unknown';
        }

        var obj = {};
        var spiesWereSet = false;

        if (!j$.isArray_(props)) {
            throw new Error('combineSpyObj requires a array of spies or spyObjects to combine spies object');
        }

        props.forEach(function(prop) {
            var methodName;

            if (j$.isSpy(prop)) {
                methodName = prop.and.identity;
                prop.and.identity = `${baseName}.${methodName}`;
                obj[methodName] = prop;
                spiesWereSet = true;
            } else if (j$.isObject_(prop)) {
                for (var propName in prop) {
                    if (prop.hasOwnProperty(propName) && j$.isSpy(prop[propName])) {
                        var identity = prop[propName].and.identity;
                        methodName = identity.substr(0, identity.indexOf('.'));
                        obj[methodName] = prop;
                        spiesWereSet = true;
                        break;
                    }
                }
            }
        });

        if (!spiesWereSet) {
            throw new Error('combineSpyObj requires a non-empty array of spies or spyObjects to combine spies object');
        }

        return obj;
    };

    var itSyncCase = function(name, handler, args) {
        it(name, function() {
            handler.apply(this, args);
        });
    };

    var itAsyncCase = function(name, handler, args) {
        it(name, function(done) {
            handler.apply(this, [done].concat(args));
        });
    };

    /**
     * Implements approach for tests data provider
     *
     * @param {Object.<string, Array>} cases
     * @param {Function} caseHandler
     */
    j$.itEachCase = function(cases, caseHandler) {
        if (!j$.isObject_(cases)) {
            throw new Error('itEachCase expects a object as first argument; received ' + j$.getType_(cases));
        }
        if (!j$.isFunction_(caseHandler)) {
            throw new Error('itEachCase expects a function as second argument; received ' + j$.getType_(caseHandler));
        }

        var casesNames = Object.keys(cases);
        var handlerArgsLength = caseHandler.length;
        var caseArgsLength = cases[casesNames[0]].length;
        var method;
        switch (handlerArgsLength - caseArgsLength) {
            case 0:
                method = itSyncCase;
                break;
            case 1:
                method = itAsyncCase;
                break;
            default:
                throw new Error('itEachCase expects cases to have ' + (handlerArgsLength) + ' arguments');
        }

        casesNames.forEach(function(caseName) {
            var caseArgs = cases[caseName];
            if (!j$.isArray_(caseArgs)) {
                throw new Error('itEachCase expects all case arguments to be an array; ' +
                    'received ' + j$.getType_(caseArgs) + ' in case "' + caseName + '"');
            } else if (caseArgs.length !== caseArgsLength) {
                throw new Error('itEachCase expects all case to have ' + (caseArgsLength) + ' arguments; ' +
                    'received ' + caseArgs.length + ' arguments in case "' + caseName + '"');
            }

            method(caseName, caseHandler, caseArgs);
        });
    };
})(window.jasmine);
