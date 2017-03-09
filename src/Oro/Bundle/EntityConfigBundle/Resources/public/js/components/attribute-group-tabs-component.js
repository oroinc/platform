define(function(require) {
    'use strict';

    var AttributeGroupTabsComponent;
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var TabCollectionView = require('oroui/js/app/views/tab-collection-view');

    AttributeGroupTabsComponent = BaseComponent.extend({
        /**
         * @type {Object}
         */
        current: null,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            var groups  = options.data;

            var first = groups[0];
            first.active = true;
            this.current = first.id;
            this.trigger(this.current, first.active);

            this.groups = new BaseCollection(groups);

            this.view = new TabCollectionView({
                el: options._sourceElement,
                animationDuration: 0,
                collection: this.groups
            });

            this.listenTo(this.groups, 'change', this.onGroupChange);
        },

        onGroupChange: function(model) {
            if (model.get('active')) {
                this.trigger(this.current, model.hasChanged('active'));
                this.trigger(model.get('id'));
                this.current = model.get('id');
            }
            model.set({'click_state': false}, {silent: true});
        },

        trigger: function(code, isActiveChanged) {
            mediator.trigger('entity-config:attribute-group:changed', {
                id: code,
                isActiveChanged: isActiveChanged
            });
        }
    });

    return AttributeGroupTabsComponent;
});
