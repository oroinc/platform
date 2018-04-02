define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    var AutoResponseRuleComponent = BaseComponent.extend({
        relatedSiblingComponents: {
            conditionBuilderComponent: 'condition-builder'
        },

        /**
         * @inheritDoc
         */
        constructor: function AutoResponseRuleComponent() {
            AutoResponseRuleComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (!this.conditionBuilderComponent) {
                throw new Error('Sibling component `conditionBuilderComponent` is required.');
            }
            this.$definitionInput = $('[data-ftid=oro_email_autoresponserule_definition]');

            this.conditionBuilderComponent.view.setValue(_.result(this.getValue(), 'filters'));

            this.listenTo(this.conditionBuilderComponent.view, 'change', this.setFiltersValue);

            AutoResponseRuleComponent.__super__.initialize.apply(this, arguments);
        },

        getValue: function() {
            var value = this.$definitionInput.val();
            return value.length ? JSON.parse(value) : {};
        },

        setFiltersValue: function(filtersValue) {
            var value = this.getValue();
            value.filters = filtersValue;
            this.$definitionInput.val(JSON.stringify(value));
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.$definitionInput;
            AutoResponseRuleComponent.__super__.dispose.call(this);
        }
    });

    return AutoResponseRuleComponent;
});
