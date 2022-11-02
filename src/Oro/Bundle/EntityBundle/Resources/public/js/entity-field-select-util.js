define(['underscore'
], function(_) {
    'use strict';

    // define a constructor
    const entityFieldUtil = function($el) {
        this.$el = $el;
    };

    /**
     * @export  oroentity/js/entity-field-select-util
     * @class   oroentity.EntityFieldSelectUtil
     */
    entityFieldUtil.prototype = {
        findEntity: function(entity) {
            return {name: entity, label: entity, plural_label: entity, icon: null};
        },

        splitFieldId: function(fieldId) {
            const result = [];
            let data = this._getData();
            const chain = fieldId.split('+');
            if (_.size(chain) > 1) {
                result.push({
                    entity: this.findEntity(this.getEntityName()),
                    label: this._getFieldGroupLabel(chain[0], data)
                });
                let prevFieldName = chain[0];
                const lastItemIndex = _.size(chain) - 2;
                _.each(_.rest(chain), (item, index) => {
                    data = this._getChildren(this._getField(prevFieldName, data));
                    const pair = this._getPair(item);
                    const label = index < lastItemIndex
                        ? this._getFieldGroupLabel(_.last(pair), data)
                        : this._getFieldLabel(_.last(pair), data);
                    result.push({
                        entity: this.findEntity(_.first(pair)),
                        label: label
                    });
                    prevFieldName = _.last(pair);
                });
            } else if (chain[0] !== '') {
                result.push({
                    entity: this.findEntity(this.getEntityName()),
                    label: this._getFieldLabel(chain[0], data)
                });
            }
            return result;
        },

        getEntityName: function() {
            return _.isUndefined(this.$el.data('entity')) ? null : this.$el.data('entity');
        },

        changeEntity: function(entityName, fields) {
            this.$el.data('entity', entityName);
            this.$el.data('data', this._convertData(fields, this.getEntityName(), null));
            this.$el.val('');
            this.$el.trigger('change');
        },

        getFieldData: function(fieldId) {
            let result = {};
            let data = this._getData();
            const chain = fieldId.split('+');
            if (_.size(chain) > 1) {
                result = this._getField(chain[0], data);
                data = this._getChildren(result);
                const lastItemIndex = _.size(chain) - 2;
                _.each(_.rest(chain), (item, index) => {
                    const fieldName = _.last(this._getPair(item));
                    result = index < lastItemIndex
                        ? this._getField(fieldName, data)
                        : this._getFieldData(fieldName, data);
                    data = this._getChildren(result);
                });
            } else if (chain[0] !== '') {
                result = this._getFieldData(chain[0], data);
            }

            return _.omit(result, ['children']);
        },

        filterData: function() {
            this._filterData(this._getData());
        },

        _getData: function() {
            const data = this.$el.data('data');
            return _.isUndefined(data) || _.isNull(data) ? [] : data;
        },

        _getFieldLabel: function(fieldName, data) {
            const fieldData = this._getFieldData(fieldName, data);
            return fieldData ? fieldData.text : null;
        },

        _getFieldGroupLabel: function(fieldName, data) {
            const field = this._getField(fieldName, data);
            return field ? field.text : null;
        },

        /**
         * Returns a pair contains class name and field name
         * Examples:
         *  item = "Acme\Entity::id" (class name = "Acme\Entity", field name = "id")
         *      result = ["Acme\Entity", "id"]
         *  item = "Acme\Entity1::Acme\Entit2::id" (class name = "Acme\Entity1", field name = "Acme\Entit2::id")
         *      result = ["Acme\Entity1", "Acme\Entit2::id"]
         *
         * @param {string} item
         * @returns {Array}
         * @private
         */
        _getPair: function(item) {
            let pair = item.split('::');
            if (_.size(pair) === 3) {
                pair = [
                    pair[0],
                    pair[1] + '::' + pair[2]
                ];
            }

            return pair;
        },

        _getField: function(fieldName, data) {
            let field = null;
            // can't use _.find because it returns first level clone of element
            _.each(data, function(element) {
                if (element.name === fieldName) {
                    field = element;
                }
            });
            return field;
        },

        _getFieldData: function(fieldName, data) {
            let fields = _.find(data, function(val) {
                return _.isUndefined(val.name);
            });
            if (_.isUndefined(fields)) {
                fields = data;
            } else if (!_.isUndefined(fields.children)) {
                fields = fields.children;
            } else {
                fields = [];
            }
            return this._getField(fieldName, fields);
        },

        _getChildren: function(data) {
            return _.isUndefined(data.children) ? [] : data.children;
        },

        _filterData: function(data) {
            _.each(data, function(item, key) {
                if (_.isUndefined(item.name)) {
                    // 'Fields' group
                    if (!_.isUndefined(item.children)) {
                        this._filterData(item.children);
                        if (_.isEmpty(item.children)) {
                            delete data[key];
                        }
                    }
                } else if (!_.isUndefined(item.relation_type)) {
                    // related field
                    if (!_.isUndefined(item.children)) {
                        this._filterData(item.children);
                    }
                } else {
                    // field
                    if (this.exclude(this._getFieldApplicableConditions(item, this.getEntityName()))) {
                        delete data[key];
                    }
                }
            }, this);
        },

        _getFieldApplicableConditions: function(field, entity) {
            return _.extend({
                entity: entity,
                field: field.name
            }, _.pick(field, ['type', 'identifier']));
        },

        _convertData: function(fields, entityName, parentFieldId) {
            const result = [];
            _.each(fields, field => {
                const fieldId = (null !== parentFieldId)
                    ? parentFieldId + '+' + entityName + '::' + field.name
                    : field.name;
                if (_.isUndefined(field.relation_type)) {
                    if (_.isUndefined(this.exclude) ||
                        !this.exclude(this._getFieldApplicableConditions(field, this.getEntityName()))) {
                        result.push(_.extend({
                            id: fieldId,
                            text: field.label
                        }, _.omit(field, ['label'])));
                    }
                } else {
                    if (!_.isUndefined(field.related_entity_fields)) {
                        result.push(_.extend({
                            text: field.label,
                            children: this._convertData(
                                field.related_entity_fields,
                                field.related_entity_name,
                                fieldId
                            )
                        }, _.omit(field, ['label', 'related_entity_fields'])));
                    } else {
                        result.push(_.extend({
                            text: field.label
                        }, _.omit(field, ['label'])));
                    }
                }
            });

            return result;
        }
    };

    return entityFieldUtil;
});
