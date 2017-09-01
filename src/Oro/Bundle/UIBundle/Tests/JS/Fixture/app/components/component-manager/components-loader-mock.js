define(function(require) {
    'use strict';

    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');

    var ComponentNeedsB = BaseComponent.extend({
        requiredSiblingComponents: {
            componentB: 'component-b'
        }
    });

    var ComponentNeedsCE = BaseComponent.extend({
        requiredSiblingComponents: {
            componentC: 'component-c',
            componentE: 'component-e'
        }
    });

    var ComponentNeedsA = BaseComponent.extend({
        requiredSiblingComponents: {
            componentA: 'component-a'
        }
    });

    var ComponentExtendNoNeedA = ComponentNeedsA.extend({
        requiredSiblingComponents: {
            componentA: false
        }
    });

    var ComponentNoNeeds = BaseComponent.extend({});

    var components = {
        'js/needs-b-component': ComponentNeedsB,
        'js/needs-ce-component': ComponentNeedsCE,
        'js/needs-a-component': ComponentNeedsA,
        'js/extend-no-need-a-component': ComponentExtendNoNeedA,
        'js/no-needs-component': ComponentNoNeeds
    };

    return function(moduleName) {
        var deferred = $.Deferred();
        setTimeout(function() {
            deferred.resolve(components[moduleName]);
        }, 0);
        return deferred.promise();
    };
});
