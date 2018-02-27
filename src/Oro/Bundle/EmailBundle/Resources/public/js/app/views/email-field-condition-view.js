define(function(require) {
    'use strict';

    var EmailFieldConditionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var FieldConditionView = require('oroquerydesigner/js/app/views/field-condition-view');
    var CustomsetFieldChoiceView = require('oroentity/js/app/views/customset-field-choice-view');

    EmailFieldConditionView = FieldConditionView.extend({
        entityData: null,

        /**
         * @inheritDoc
         */
        constructor: function EmailFieldConditionView() {
            EmailFieldConditionView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
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
            var fields = this.entityData.fields.map(function(field) {
                return {
                    id: field.name,
                    text: field.label
                };
            });
            var choiceInputData = [{
                text: __('oro.entity.field_choice.fields'),
                children: fields
            }];
            var fieldChoiceView = new CustomsetFieldChoiceView({
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
