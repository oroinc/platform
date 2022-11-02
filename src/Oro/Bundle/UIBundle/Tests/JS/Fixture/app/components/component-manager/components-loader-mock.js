define(function(require) {
    'use strict';

    const $ = require('jquery');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const ComponentNeedsB = BaseComponent.extend({
        relatedSiblingComponents: {
            componentB: 'component-b'
        },

        /**
         * @inheritdoc
         */
        constructor: function ComponentNeedsB(options) {
            ComponentNeedsB.__super__.constructor.call(this, options);
        }
    });

    const ComponentNeedsCE = BaseComponent.extend({
        relatedSiblingComponents: {
            componentC: 'component-c',
            componentE: 'component-e'
        },

        /**
         * @inheritdoc
         */
        constructor: function ComponentNeedsCE(options) {
            ComponentNeedsCE.__super__.constructor.call(this, options);
        }
    });

    const ComponentNeedsA = BaseComponent.extend({
        relatedSiblingComponents: {
            componentA: 'component-a'
        },

        /**
         * @inheritdoc
         */
        constructor: function ComponentNeedsA(options) {
            ComponentNeedsA.__super__.constructor.call(this, options);
        }
    });

    const ComponentExtendNoNeedA = ComponentNeedsA.extend({
        relatedSiblingComponents: {
            componentA: false
        },

        /**
         * @inheritdoc
         */
        constructor: function ComponentExtendNoNeedA(options) {
            ComponentExtendNoNeedA.__super__.constructor.call(this, options);
        }
    });

    const ComponentNoNeeds = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function ComponentNoNeeds(options) {
            ComponentNoNeeds.__super__.constructor.call(this, options);
        }
    });

    const FooComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function FooComponent(options) {
            FooComponent.__super__.constructor.call(this, options);
        }
    });

    const BarComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function BarComponent(options) {
            BarComponent.__super__.constructor.call(this, options);
        }
    });

    const components = {
        'js/needs-b-component': ComponentNeedsB,
        'js/needs-ce-component': ComponentNeedsCE,
        'js/needs-a-component': ComponentNeedsA,
        'js/extend-no-need-a-component': ComponentExtendNoNeedA,
        'js/no-needs-component': ComponentNoNeeds,
        'js/foo-component': FooComponent,
        'js/bar-component': BarComponent
    };

    return function(moduleName) {
        const deferred = $.Deferred();
        setTimeout(function() {
            deferred.resolve(components[moduleName]);
        }, 0);
        return deferred.promise();
    };
});
