define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const FieldConditionView = require('oroquerydesigner/js/app/views/field-condition-view');
    const CustomsetFieldChoiceView = require('oroentity/js/app/views/customset-field-choice-view');

    const EmailFieldConditionView = FieldConditionView.extend({
        entityData: null,

        /**
         * @inheritdoc
         */
        constructor: function EmailFieldConditionView(options) {
            EmailFieldConditionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.entityData = options.entityData;
            EmailFieldConditionView.__super__.initialize.call(this, options);
        },

        getApplicableConditions: function() {
            return {
                type: 'string'
            };
        },

        initChoiceInputView: function() {
            const fields = this.entityData.fields.map(function(field) {
                return {
                    id: field.name,
                    text: field.label
                };
            });
            const choiceInputData = [{
                text: __('oro.entity.field_choice.fields'),
                children: fields
            }];
            const fieldChoiceView = new CustomsetFieldChoiceView({
                autoRender: true,
                el: this.$choiceInput,
                select2: _.extend({}, this.options.fieldChoice.select2, {
                    data: choiceInputData,
                    formatSelectionTemplate: this.choiceSelectionTemplate,
                    formatBreadcrumbItem: function(item) {
                        return item.label;
                    },
                    breadcrumbs: function() {
                        return [_.pick(this.entityData, 'label')];
                    }.bind(this)
                })
            });

            return $.when(fieldChoiceView);
        }
    });

    return EmailFieldConditionView;
});
