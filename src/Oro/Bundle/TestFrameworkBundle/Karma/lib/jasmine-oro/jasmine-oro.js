(function(jasmine) {
    'use strict';

    /**
     * Combines an object with multiple {@link Spy}s or {@link jasmine.createSpyObj}s as its members.
     * @name jasmine.combineSpyObj
     * @function
     * @param {String} [baseName] - Base name for the spies in the object.
     * @param {Array.<Function|Object>} props - Array of spy functions or spy objects.
     * @return {Object}
     */
    jasmine.combineSpyObj = function(baseName, props) {
        var baseNameIsCollection = jasmine.isObject_(baseName) || jasmine.isArray_(baseName);

        if (baseNameIsCollection && jasmine.util.isUndefined(props)) {
            props = baseName;
            baseName = 'unknown';
        }

        var obj = {};
        var spiesWereSet = false;

        if (!jasmine.isArray_(props)) {
            throw new Error('combineSpyObj requires a array of spies or spyObjects to combine spies object');
        }

        props.forEach(function(prop) {
            var methodName;

            if (jasmine.isSpy(prop)) {
                methodName = prop.and.identity();
                prop.and.identity = function() {
                    return baseName + '.' + methodName;
                };
                obj[methodName] = prop;
                spiesWereSet = true;
            } else if (jasmine.isObject_(prop)) {
                for (var propName in prop) {
                    if (prop.hasOwnProperty(propName) && jasmine.isSpy(prop[propName])) {
                        var identity = prop[propName].and.identity();
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
})(window.jasmine);