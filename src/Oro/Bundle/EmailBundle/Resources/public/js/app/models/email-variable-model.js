/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var EmailVariableModel,
        _ = require('underscore'),
        BaseModel = require('oroui/js/app/models/base/model')

    /**
     * @export oroemail/js/app/models/email-variable-model
     */
    EmailVariableModel = BaseModel.extend({
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
         * @property {array} Each item is {Object} with 'relatedEntityName' and 'fieldName' properties
         */
        path: [],

        /**
         * @returns {string}
         */
        getEntityName: function () {
            return this.entityName;
        },

        /**
         * @returns {string}
         */
        getEntityLabel: function () {
            return this.entityLabel;
        },

        /**
         * @param {string} entityName
         * @param {string} entityLabel
         */
        setEntity: function (entityName, entityLabel) {
            this.entityName = entityName;
            this.entityLabel = entityLabel;
            this.path = [];
            this.trigger('change:entity');
        },

        /**
         * @returns {string} For example '/field1/field2'. The empty string represents the root
         */
        getPath: function () {
            var result = '';
            _.each(this.path, function (item) {
                result += '/' + item.fieldName;
            });
            return result;
        },

        /**
         * @param {string} path For example '/field1/field2'. The empty string represents the root
         */
        setPath: function (path) {
            this.path = [];
            _.each(path.split('/'), function (fieldName) {
                if (fieldName) {
                    this.path.push({
                        relatedEntityName: this._getRelatedEntityName(this._getCurrentEntityName(), fieldName),
                        fieldName: fieldName
                    });
                }
            }, this);
            this.trigger('change:entity');
        },

        /**
         * @returns {Object}
         */
        getSystemVariables: function () {
            return this.attributes.system;
        },

        /**
         * @returns {Object}
         */
        getEntityVariables: function () {
            var entityName = this._getCurrentEntityName();
            if (entityName && _.has(this.attributes.entity, entityName)) {
                return this.attributes.entity[entityName];
            }
            return {};
        },

        /**
         * @returns {string}
         * @private
         */
        _getCurrentEntityName: function () {
            var lastItem = _.last(this.path);
            return lastItem ? lastItem.relatedEntityName : this.entityName;
        },

        /**
         * @param {string} entityName
         * @param {string} fieldName
         * @returns {string}
         * @private
         */
        _getRelatedEntityName: function (entityName, fieldName) {
            return this.attributes.entity[entityName][fieldName].related_entity_name;
        }
    });

    return EmailVariableModel;
});
