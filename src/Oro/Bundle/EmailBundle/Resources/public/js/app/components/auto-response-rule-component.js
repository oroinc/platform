define([
    'jquery',
    'underscore',
    'oroui/js/app/components/base/component',
    'oroentity/js/fields-loader',
    'oroquerydesigner/js/condition-builder'
], function($, _, BaseComponent) {
    'use strict';

    var AutoResponseRuleComponent = BaseComponent.extend({
        initialize: function(options) {
            this._initLoader(options);
            this._initFieldCondition(options);
            this._initBuilder(options);

            AutoResponseRuleComponent.__super__.initialize.apply(this, arguments);
        },

        _initLoader: function(options) {
            var $entityChoice = $('[data-ftid=oro_email_autoresponserule_entity]');
            $entityChoice.fieldsLoader();
            $entityChoice.fieldsLoader('setFieldsData', options.data);
        },

        _initFieldCondition: function(options) {
            var $criteria = $('#filter-criteria-list');
            var $fieldCondition = $criteria.find('[data-criteria=condition-item]');
            if (!_.isEmpty($fieldCondition)) {
                $.extend(true, $fieldCondition.data('options'), {
                    filters: options.metadata.filters,
                    hierarchy: options.metadata.hierarchy
                });
            }
        },

        _initBuilder: function(options) {
            var $builder = $('#oro_email_autoresponserule-condition-builder');
            this.$storage = $('[data-ftid=oro_email_autoresponserule_definition]');
            $builder.conditionBuilder({
                criteriaListSelector: '#filter-criteria-list'
            });
            $builder.conditionBuilder('setValue', this.load('filters'));
            $builder.on('changed', _.bind(function() {
                this.save($builder.conditionBuilder('getValue'), 'filters');
            }, this));
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
