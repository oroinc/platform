define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export oroemail/js/app/models/email-variable-model
     */
    const EmailVariableModel = BaseModel.extend({
        defaults: {
            system: [],
            entity: []
        },

        /**
         * @property {string}
         */
        entityName: null,

        /**
         * @property {string}
         */
        entityLabel: null,

        /**
         * @property {array} Each item is {Object} with 'relatedEntityName', 'fieldName' and 'fieldLabel' properties
         */
        path: [],

        /**
         * @inheritdoc
         */
        constructor: function EmailVariableModel(...args) {
            EmailVariableModel.__super__.constructor.apply(this, args);
        },

        /**
         * @returns {string}
         */
        getEntityName: function() {
            return this.entityName;
        },

        /**
         * @returns {string}
         */
        getEntityLabel: function() {
            return this.entityLabel;
        },

        /**
         * @param {string} entityName
         * @param {string} entityLabel
         */
        setEntity: function(entityName, entityLabel) {
            this.entityName = entityName;
            this.entityLabel = entityLabel;
            this.path = [];
            this.trigger('change:entity');
        },

        /**
         * @returns {string} For example '/field1/field2'. The empty string represents the root
         */
        getPath: function() {
            let result = '';
            _.each(this.path, function(item) {
                result += '/' + item.fieldName;
            });
            return result;
        },

        /**
         * @returns {array}
         */
        getPathLabels: function() {
            const result = [];
            _.each(this.path, function(item) {
                result[item.fieldName] = item.fieldLabel;
            });
            return result;
        },

        /**
         * @param {string} path For example '/field1/field2'. The empty string represents the root
         */
        setPath: function(path) {
            this.path = [];
            _.each(path.split('/'), function(fieldName) {
                if (fieldName) {
                    this.path.push({
                        relatedEntityName: this._getRelatedEntityName(this._getCurrentEntityName(), fieldName),
                        fieldName: fieldName,
                        fieldLabel: this._getEntityLabel(this._getCurrentEntityName(), fieldName)
                    });
                }
            }, this);
            this.trigger('change:entity');
        },

        /**
         * @returns {Object}
         */
        getSystemVariables: function() {
            return this.attributes.system;
        },

        /**
         * @returns {Object}
         */
        getEntityVariables: function() {
            const entityName = this._getCurrentEntityName();
            if (entityName && _.has(this.attributes.entity, entityName)) {
                return this.attributes.entity[entityName];
            }
            return {};
        },

        /**
         * @returns {string}
         * @private
         */
        _getCurrentEntityName: function() {
            const lastItem = _.last(this.path);
            return lastItem ? lastItem.relatedEntityName : this.entityName;
        },

        /**
         * @param {string} entityName
         * @param {string} fieldName
         * @returns {string}
         * @private
         */
        _getRelatedEntityName: function(entityName, fieldName) {
            return this.attributes.entity[entityName][fieldName].related_entity_name;
        },

        /**
         * @param {string} entityName
         * @param {string} fieldName
         * @returns {string}
         * @private
         */
        _getEntityLabel: function(entityName, fieldName) {
            return this.attributes.entity[entityName][fieldName].label;
        }
    });

    return EmailVariableModel;
});
