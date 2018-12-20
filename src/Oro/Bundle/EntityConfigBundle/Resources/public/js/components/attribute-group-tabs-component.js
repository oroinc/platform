define(function(require) {
    'use strict';

    var AttributeGroupTabsComponent;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var TabCollectionView = require('oroui/js/app/views/tab-collection-view');

    AttributeGroupTabsComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function AttributeGroupTabsComponent() {
            AttributeGroupTabsComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            var data = _.each(options.data, function(item) {
                item.uniqueId = _.uniqueId(item.id);
            });

            this.groups = new BaseCollection(data);

            var first = this.groups.first();
            first.set('active', true);
            this.triggerGroupChange(first, true);

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
                this.triggerGroupChange(model);
            }
        },

        /**
         * Triggers global event via mediator and pass params to listeners
         *
         * @param {Backbone.Model} model
         * @param {boolean} initialize
         */
        triggerGroupChange: function(model, initialize) {
            mediator.trigger('entity-config:attribute-group:changed', model, initialize);
        }
    });

    return AttributeGroupTabsComponent;
});
