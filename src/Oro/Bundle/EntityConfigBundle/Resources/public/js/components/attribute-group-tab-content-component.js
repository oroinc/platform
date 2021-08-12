define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const AttributeGroupTabContentComponent = BaseComponent.extend({
        relatedSiblingComponents: {
            // tab content requires group tabs component
            tabsComponent: 'attribute-group-tabs-component'
        },

        id: 0,

        el: null,

        /**
         * @inheritdoc
         */
        constructor: function AttributeGroupTabContentComponent(options) {
            AttributeGroupTabContentComponent.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.id = options.id;
            this.el = options._sourceElement;

            const {tabsComponent} = options.relatedSiblingComponents;
            if (tabsComponent) {
                const tab = tabsComponent.getGroupById(this.id);
                if (tab && tab.get('active')) {
                    this._show();
                }
            }

            this.listenTo(mediator, 'entity-config:attribute-group:changed', this.onGroupChange);
        },

        onGroupChange: function(model) {
            if (model.get('id') === this.id) {
                this._show();
            }
        },

        _show: function() {
            this.el.siblings('.' + this.el[0].className).hide();
            this.el.show();
            mediator.trigger('layout:reposition');
        }
    });

    return AttributeGroupTabContentComponent;
});
