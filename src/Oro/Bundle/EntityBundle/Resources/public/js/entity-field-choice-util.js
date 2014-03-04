/*global define*/
define(['underscore'
    ], function (_) {
    'use strict';

    // define a constructor
    var entityFieldUtil = function ($el) {
        this.$el = $el;
    };

    /**
     * @export  oroentity/js/entity-field-choice-util
     * @class   oroentity.EntityFieldChoiceUtil
     */
    entityFieldUtil.prototype = {
        /** @property */
        optGroupTemplate: _.template(
            '<optgroup label="<%- label %>">' +
                '<%= options %>' +
            '</optgroup>'
        ),

        /** @property */
        optionTemplate: _.template(
            '<option value="<%- name %>"<% _.each(_.omit(obj, ["name", "related_entity_fields"]), function (val, key) { %> data-<%- key %>="<%- val %>"<% }) %>>' +
                '<%- label %>' +
            '</option>'
        ),

        findEntity: function (entity) {
            return {name: entity, label: entity, plural_label: entity, icon: null};
        },

        splitFieldId: function (fieldId) {
            var result = [];
            if (fieldId != '') {
                result.push({
                    entity: this.findEntity(this.getEntityName()),
                    label: this._getFieldLabel(fieldId)
                });
            }
            return result;
        },

        getEntityName: function () {
            return _.isUndefined(this.$el.data('entity')) ? null : this.$el.data('entity');
        },

        changeEntity: function (entityName, fields) {
            this.$el.data('entity', entityName);
            var emptyItem = this.$el.find('option[value=""]');
            this.$el.empty();
            if (emptyItem.length > 0) {
                this.$el.append(this.optionTemplate({name: '', label: emptyItem.text()}));
            }
            var content = this._buildSelectContent(fields);
            if (content != '') {
                this.$el.append(content);
            }
            this.$el.val(this.$el.is('[multiple]') ? [] : '');
            this.$el.trigger('change');
        },

        getFieldData: function (fieldId) {
            return this._getOptionElement(fieldId).data();
        },

        filterData: function () {
            this.$el.find('option').each(_.partial(function (that) {
                if (that.exclude(that._getFieldApplicableConditions($(this).data(), that.getEntityName()))) {
                    $(this).remove();
                }
            }, this));
        },

        _getFieldLabel: function (fieldId) {
            return this._getOptionElement(fieldId).data('label');
        },

        _getFieldGroupLabel: function (fieldId) {
            return this._getOptionElement(fieldId).parent().attr('label');
        },

        _getOptionElement: function (value) {
            return this.$el.find('option[value="' + value.replace(/\\/g,"\\\\").replace(/:/g,"\\:") + '"]');
        },

        _getFieldApplicableConditions: function (field, entity) {
            return _.extend({
                    entity: entity,
                    field: field.name
                },
                _.pick(field, ['type', 'identifier'])
            );
        },

        _buildSelectContent: function (fields) {
            var sFields = '';
            var sRelations = '';
            _.each(fields, _.bind(function (field) {
                if (_.isUndefined(field['related_entity_name'])) {
                    if (_.isUndefined(this.exclude)
                        || !this.exclude(this._getFieldApplicableConditions(field, this.getEntityName()))) {
                        sFields += this.optionTemplate(field);
                    }
                } else {
                    sRelations += this.optionTemplate(field);
                }
            }, this));

            if (sRelations == '') {
                return sFields;
            }
            var result = '';
            if (sFields != '') {
                result += this.optGroupTemplate({
                    label: this.fieldsLabel,
                    options: sFields
                });
            }
            result += this.optGroupTemplate({
                label: this.relatedLabel,
                options: sRelations
            });
            return result;
        }
    };

    return entityFieldUtil;
});
