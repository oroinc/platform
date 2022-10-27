define(function(require) {
    'use strict';

    const choiceTemplate = require('tpl-loader!orofilter/templates/filter/embedded/simple-choice-filter.html');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const FieldConditionView = require('oroquerydesigner/js/app/views/field-condition-view');
    const CustomsetFieldChoiceView = require('oroentity/js/app/views/customset-field-choice-view');
    const ChoiceFilter = require('oro/filter/choice-filter');
    const MultiSelectFilter = require('oro/filter/multiselect-filter');
    const activityConditionTemplate = require('tpl-loader!oroactivitylist/templates/activity-condition.html');

    const ActivityConditionView = FieldConditionView.extend({
        TYPE_CHOICE_ENTITY: '$activity',
        template: activityConditionTemplate,
        choiceSelectionTemplate: '<span class="entity-field-path"><span></span><b><%-text %></b></span>',
        choiceInputData: [{
            text: __('oro.entity.field_choice.fields'),
            children: [
                {
                    id: '$createdAt',
                    text: __('oro.activitylist.created_at.label')
                }, {
                    id: '$updatedAt',
                    text: __('oro.activitylist.updated_at.label')
                }
            ]
        }],

        getDefaultOptions: function() {
            const defaultOptions = ActivityConditionView.__super__.getDefaultOptions.call(this);
            return _.extend({}, defaultOptions, {
                listOptions: {},
                filters: {},
                rootEntity: null,
                filterContainer: '<span class="active-filter">',
                activityChoiceContainerSelector: '.activity-choice-container',
                typeChoiceContainerSelector: '.type-choice-container'
            });
        },

        /**
         * @inheritdoc
         */
        constructor: function ActivityConditionView(options) {
            ActivityConditionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ActivityConditionView.__super__.initialize.call(this, options);
            _.extend(this.options.fieldChoice, {
                applicableConditionsCallback: this.applicableConditionsCallback.bind(this)
            });
        },

        onChoiceInputReady: function(provider) {
            ActivityConditionView.__super__.onChoiceInputReady.call(this, provider);

            const data = $.extend(true, {
                criterion: {
                    data: {
                        filterType: 'hasActivity',
                        activityType: {},
                        activityFieldName: ''
                    }
                }
            }, this.getValue());

            this._attachActivityFilter(data.criterion.data.filterType);
            this._attachTypeFilter(this.options.listOptions, data.criterion.data.activityType);

            const filterOptions = _.findWhere(this.options.filters, {
                type: 'datetime'
            });
            if (!filterOptions) {
                throw new Error('Cannot find filter "datetime"');
            }
        },

        _attachActivityFilter: function(filterType) {
            this.activityFilter = new ChoiceFilter({
                el: this.$(this.options.activityChoiceContainerSelector),
                caret: '',
                template: choiceTemplate,
                choices: {
                    hasActivity: __('oro.activityCondition.hasActivity'),
                    hasNotActivity: __('oro.activityCondition.hasNotActivity')
                }
            });
            this.activityFilter.setValue({
                type: filterType
            });
            this.activityFilter.render();
            this.listenTo(this.activityFilter, 'update typeChange', this._onUpdate);
        },

        _attachTypeFilter: function(listOptions, activityType) {
            const typeChoices = _.mapObject(listOptions, _.property('label'));

            this.typeFilter = new MultiSelectFilter({
                showLabel: false,
                choices: typeChoices,
                widgetOptions: {
                    refreshNotOpened: true
                }
            });
            this.typeFilter.setValue(activityType);
            this.$(this.options.typeChoiceContainerSelector).append(this.typeFilter.render().$el);
            this.listenTo(this.typeFilter, 'update', this._onTypeFilterUpdate);
        },

        _onTypeFilterUpdate: function() {
            this._onUpdate();
            const oldEntity = this.$choiceInput.data('entity');
            const newEntity = this.TYPE_CHOICE_ENTITY;

            if (oldEntity !== newEntity) {
                this.subview('choice-input').setValue('');
                this.$filterContainer.empty();
                if (this.filter) {
                    this.filter.reset();
                }
            }
        },

        fieldChoiceResultsCallback: function(results) {
            if (_.isEmpty(results)) {
                return results;
            }

            let fields = _.first(results).children;
            const activities = _.filter(fields, function(item) {
                return item.id[0] === '$';
            });
            fields = _.reject(fields, function(item) {
                return item.id[0] === '$';
            });

            _.first(results).children = fields;
            if (_.isEmpty(fields)) {
                results.shift();
            }
            results.unshift({
                text: 'Activity',
                children: activities
            });

            return results;
        },

        applicableConditionsCallback: function(result, fieldId) {
            if (_.isEmpty(result) && _.contains(['createdAt', 'updatedAt'], fieldId)) {
                result = {
                    parent_entity: null,
                    entity: null,
                    field: fieldId,
                    type: 'datetime'
                };
            }

            return result;
        },

        _getFilterCriterion: function() {
            const filter = {
                filter: this.filter.name,
                data: this.filter.getValue()
            };

            if (this.filter.filterParams) {
                filter.params = this.filter.filterParams;
            }

            return {
                filter: 'activityList',
                data: {
                    filterType: this.activityFilter.getType(),
                    activityType: this.typeFilter.getValue(),
                    filter: filter,
                    entityClassName: this.options.rootEntity,
                    activityFieldName: this.getColumnName()
                }
            };
        },

        _getInitialChoiceInputValue: function() {
            const criterion = _.result(this.getValue(), 'criterion');
            if (criterion) {
                return _.result(criterion.data, 'activityFieldName');
            }
        },

        _getFilterValue: function() {
            const criterion = _.result(this.getValue(), 'criterion');
            if (criterion && criterion.data) {
                return _.result(criterion.data.filter, 'data');
            }
        },

        getApplicableConditions: function() {
            return {
                type: 'datetime'
            };
        },

        _collectValue: function() {
            const value = ActivityConditionView.__super__._collectValue.call(this);
            return _.omit(value, 'columnName');
        },

        setActivityExistence: function(value) {
            this.activityFilter.setValue({type: value});
        },

        setActivityTypes: function(values) {
            this.typeFilter.setValue({value: values});
        },

        initChoiceInputView: function() {
            const fieldChoiceView = new CustomsetFieldChoiceView({
                autoRender: true,
                el: this.$choiceInput,
                select2: _.extend({}, this.options.fieldChoice.select2, {
                    data: this.choiceInputData,
                    formatSelectionTemplate: this.choiceSelectionTemplate
                })
            });

            return $.when(fieldChoiceView);
        }
    });

    return ActivityConditionView;
});
