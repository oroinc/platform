define(function(require) {
    'use strict';

    var fieldFilterers;
    var _ = require('underscore');

    fieldFilterers = {
        /**
         * Check if the field is matched to any rule of set
         *
         * @param {Object} field
         * @param {[Object|string]} rules
         * @return {boolean}
         */
        anyRule: function(field, rules) {
            return _.any(rules, function(rule) {
                // rule can be a property name or an object with data to compare
                return _.isString(rule) ? Boolean(field[rule]) : _.isMatch(field, rule);
            });
        },

        /**
         * Check if the field complies to the options filter set
         *
         * @param {Object} field
         * @return {boolean}
         * @this EntityStructureDataProvider
         */
        options: function(field) {
            return _.every(this.regularOptionsFilter, function(value, optionName) {
                return Boolean(field.options && field.options[optionName]) === value;
            });
        },

        /**
         * Checks if the field is unidirectional
         *
         * @param {Object} field
         * @return {boolean}
         * @this EntityStructureDataProvider
         */
        unidirectional: function(field) {
            var expected = this.optionsFilter.unidirectional;
            return expected === (field.name.indexOf('::') !== -1);
        },

        /**
         * Checks if the field is auditable
         *
         * @param {Object} field
         * @param {string} entityClassName
         * @return {boolean}
         * @this EntityStructureDataProvider
         */
        auditable: function(field, entityClassName) {
            var index;
            var expected = this.optionsFilter.auditable;
            var entity = this.collection.getEntityModelByClassName(entityClassName);
            if (expected && !(entity && _.result(entity.get('options'), 'auditable'))) {
                return false;
            }

            index = field.name.indexOf('::');
            if (index !== -1) {
                // unidirectional field
                entityClassName  = field.name.substr(0, index);
                entity = this.collection.getEntityModelByClassName(entityClassName);
                if (expected && !(entity && _.result(entity.get('options'), 'auditable'))) {
                    return false;
                }
                entity = this.collection.getEntityModelByClassName(entityClassName);

                field = _.find(entity.get('fields'), {name: field.name.substr(index + 2)});
            }

            return expected === Boolean(_.result(field.options, 'auditable'));
        },

        /**
         * Checks if the field is a relation
         *
         * @param {Object} field
         * @return {boolean}
         * @this EntityStructureDataProvider
         */
        relation: function(field) {
            var expected = this.optionsFilter.relation;
            return expected === Boolean(field.relatedEntityName);
        },

        /**
         * Checks if the field does not match any exclude rule
         *  (if matches -- will be excluded)
         *
         * @param {Object} field
         * @return {boolean}
         * @this EntityStructureDataProvider
         */
        exclude: function(field) {
            return !fieldFilterers.anyRule(field, this.exclude);
        },

        /**
         * Checks if the field matches any include rule
         *  (if matches -- will be include)
         *
         * @param {Object} field
         * @return {boolean}
         * @this EntityStructureDataProvider
         */
        include: function(field) {
            return fieldFilterers.anyRule(field, this.include);
        }
    };

    return fieldFilterers;
});
