define(function(require) {
    'use strict';

    var AttributeGroupTabsComponent;
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var TabCollectionView = require('oroui/js/app/views/tab-collection-view');

    AttributeGroupTabsComponent = BaseComponent.extend({
        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.groups = new BaseCollection(options.data);

            var first = this.groups.first();
            first.set('active', true);
            this.trigger(first, true);

            this.view = new TabCollectionView({
                el: options._sourceElement,
                animationDuration: 0,
                collection: this.groups
            });

            this.listenTo(this.groups, 'change', this.onGroupChange);
            this.listenTo(this.groups, 'select', this.onGroupChange);
        },

        onGroupChange: function(model) {
            if (model.get('active') === true) {
                this.trigger(model);
            }
        },

        trigger: function(model, initialize) {
            mediator.trigger('entity-config:attribute-group:changed', model, initialize);
        }
    });

    return AttributeGroupTabsComponent;
});
