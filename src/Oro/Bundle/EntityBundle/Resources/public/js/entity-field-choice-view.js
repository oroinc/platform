/* global define */
define(['underscore', 'backbone'],
function(_, Backbone) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/entity-field-choice-view
     * @class   oro.EntityFieldChoiceView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property {Object} */
        options: {
            fieldsLabel: null,
            relatedLabel: null,
            findEntity: null
        },

        /** @property */
        optGroupTemplate: _.template(
            '<optgroup label="<%- label %>">' +
                '<%= options %>' +
            '</optgroup>'
        ),

        /** @property */
        optionTemplate: _.template(
            '<option value="<%- name %>"<% _.each(_.omit(obj, ["name"]), function (val, key) { %> data-<%- key.replace(/_/g,"-") %>="<%- val %>"<% }) %>>' +
                '<%- label %>' +
            '</option>'
        ),

        initialize: function() {
            if (!_.isNull(this.options.findEntity)) {
                this.$el.data('entity-field-util').findEntity = this.options.findEntity;
            }
        },

        changeEntity: function (entity, fields) {
            this.$el.data('entity', entity);
            var emptyItem = this.$el.find('option[value=""]');
            this.$el.empty();
            if (emptyItem.length > 0) {
                this.$el.append(this.optionTemplate({name: '', label: emptyItem.text()}));
            }
            var content = this._getSelectContent(fields);
            if (content != '') {
                this.$el.append(content);
            }
            this.$el.val(this.$el.is('[multiple]') ? [] : '');
            this.$el.trigger('change');
        },

        splitValue: function (value) {
            return this.$el.data('entity-field-util').splitValue(value);
        },

        _getSelectContent: function (fields) {
            var sFields = '';
            var sRelations = '';
            var isRelationsWithFields = false;
            _.each(fields, _.bind(function (field) {
                if (_.isUndefined(field['related_entity_name'])) {
                    sFields += this.optionTemplate(field);
                } else {
                    if (!_.isUndefined(field['related_entity_fields'])) {
                        isRelationsWithFields = true;
                        var sRelatedFields = '';
                        _.each(field['related_entity_fields'], _.bind(function (relatedField) {
                            relatedField = _.clone(relatedField);
                            relatedField['name'] =
                                field['name'] + ',' +
                                field['related_entity_name'] + '::' + relatedField['name'];
                            sRelatedFields += this.optionTemplate(relatedField);
                        }, this));
                        sRelations += this.optGroupTemplate({
                            label: field['label'],
                            options: sRelatedFields
                        });
                    } else {
                        sRelations += this.optionTemplate(field);
                    }
                }
            }, this));

            if (sRelations == '') {
                return sFields;
            }
            var result = '';
            if (sFields != '') {
                result += this.optGroupTemplate({
                    label: this.options.fieldsLabel,
                    options: sFields
                });
            }
            if (isRelationsWithFields) {
                result += sRelations;
            } else {
                result += this.optGroupTemplate({
                    label: this.options.relatedLabel,
                    options: sRelations
                });
            }
            return result;
        }
    });
});
