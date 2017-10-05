define([
    'jquery',
    'underscore',
    'oroui/js/app/components/base/component',
    'oroentity/js/fields-loader'
], function($, _, BaseComponent) {
    'use strict';

    var AutoResponseRuleComponent = BaseComponent.extend({
        requiredSiblingComponents: {
            conditionBuilderComponent: 'condition-builder'
        },

        initialize: function(options) {
            this.$storage = $('[data-ftid=oro_email_autoresponserule_definition]');

            this._initLoader(options);

            this.conditionBuilderComponent.view.setValue(this.load('filters'));
            this.listenTo(this.conditionBuilderComponent.view, 'change', function(value) {
                this.save(value, 'filters');
            });

            AutoResponseRuleComponent.__super__.initialize.apply(this, arguments);
        },

        _initLoader: function(options) {
            var $entityChoice = $('[data-ftid=oro_email_autoresponserule_entity]');
            $entityChoice.fieldsLoader();
            $entityChoice.fieldsLoader('setFieldsData', options.data);
        },

        load: function(key) {
            var data = {};
            var json = this.$storage.val();
            if (json) {
                try {
                    data = JSON.parse(json);
                } catch (e) {
                    return undefined;
                }
            }
            return key ? data[key] : data;
        },

        save: function(value, key) {
            var data = this.load();
            if (key) {
                data[key] = value;
            } else {
                data = key;
            }
            this.$storage.val(JSON.stringify(data));
        }
    });

    return AutoResponseRuleComponent;
});
