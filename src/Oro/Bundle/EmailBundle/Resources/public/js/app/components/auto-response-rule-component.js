define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const AutoResponseRuleComponent = BaseComponent.extend({
        relatedSiblingComponents: {
            conditionBuilderComponent: 'condition-builder'
        },

        /**
         * @inheritdoc
         */
        constructor: function AutoResponseRuleComponent(options) {
            AutoResponseRuleComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (!this.conditionBuilderComponent) {
                throw new Error('Sibling component `conditionBuilderComponent` is required.');
            }
            this.$definitionInput = $('[data-ftid=oro_email_autoresponserule_definition]');

            this.conditionBuilderComponent.view.setValue(_.result(this.getValue(), 'filters'));

            this.listenTo(this.conditionBuilderComponent.view, 'change', this.setFiltersValue);

            AutoResponseRuleComponent.__super__.initialize.call(this, options);
        },

        getValue: function() {
            const value = this.$definitionInput.val();
            return value.length ? JSON.parse(value) : {};
        },

        setFiltersValue: function(filtersValue) {
            const value = this.getValue();
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
