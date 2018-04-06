define(function(require) {
    'use strict';

    var AttributeGroupTabContentComponent;
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    AttributeGroupTabContentComponent = BaseComponent.extend({
        id: 0,

        el: null,

        /**
         * @inheritDoc
         */
        constructor: function AttributeGroupTabContentComponent() {
            AttributeGroupTabContentComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.id = options.id;
            this.el = options._sourceElement;

            this.listenTo(mediator, 'entity-config:attribute-group:changed', this.onGroupChange);
        },

        onGroupChange: function(model) {
            if (model.get('id') === this.id) {
                this.el.siblings('.' + this.el[0].className).hide();
                this.el.show();
                mediator.trigger('layout:reposition');
            }
        }
    });

    return AttributeGroupTabContentComponent;
});
