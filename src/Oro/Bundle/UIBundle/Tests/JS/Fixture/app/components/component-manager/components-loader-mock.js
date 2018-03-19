define(function(require) {
    'use strict';

    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');

    var ComponentNeedsB = BaseComponent.extend({
        relatedSiblingComponents: {
            componentB: 'component-b'
        },

        /**
         * @inheritDoc
         */
        constructor: function ComponentNeedsB() {
            ComponentNeedsB.__super__.constructor.apply(this, arguments);
        }
    });

    var ComponentNeedsCE = BaseComponent.extend({
        relatedSiblingComponents: {
            componentC: 'component-c',
            componentE: 'component-e'
        },

        /**
         * @inheritDoc
         */
        constructor: function ComponentNeedsCE() {
            ComponentNeedsCE.__super__.constructor.apply(this, arguments);
        }
    });

    var ComponentNeedsA = BaseComponent.extend({
        relatedSiblingComponents: {
            componentA: 'component-a'
        },

        /**
         * @inheritDoc
         */
        constructor: function ComponentNeedsA() {
            ComponentNeedsA.__super__.constructor.apply(this, arguments);
        }
    });

    var ComponentExtendNoNeedA = ComponentNeedsA.extend({
        relatedSiblingComponents: {
            componentA: false
        },

        /**
         * @inheritDoc
         */
        constructor: function ComponentExtendNoNeedA() {
            ComponentExtendNoNeedA.__super__.constructor.apply(this, arguments);
        }
    });

    var ComponentNoNeeds = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function ComponentNoNeeds() {
            ComponentNoNeeds.__super__.constructor.apply(this, arguments);
        }
    });

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
