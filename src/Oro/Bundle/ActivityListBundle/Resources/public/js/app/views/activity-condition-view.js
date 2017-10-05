define(function(require) {
    'use strict';

    var ActivityConditionView;
    var choiceTemplate = require('tpl!orofilter/templates/filter/embedded/simple-choice-filter.html');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var FieldConditionView = require('oroquerydesigner/js/app/views/field-condition-view');
    var ChoiceFilter = require('oro/filter/choice-filter');
    var MultiSelectFilter = require('oro/filter/multiselect-filter');
    var activityConditionTemplate = require('tpl!oroactivitylist/templates/activity-condition.html');

    ActivityConditionView = FieldConditionView.extend({
        TYPE_CHOICE_ENTITY: '$activity',
        template: activityConditionTemplate,
        getDefaultOptions: function() {
            var defaultOptions = ActivityConditionView.__super__.getDefaultOptions.call(this);
            return _.extend({}, defaultOptions, {
                listOption: {},
                filters: {},
                entitySelector: null,
                filterContainer: '<span class="active-filter">',
                activityChoiceContainerSelector: '.activity-choice-container',
                typeChoiceContainerSelector: '.type-choice-container'
            });
        },

        render: function() {
            this.$fieldsLoader = $(this.options.fieldsLoaderSelector);

            _.extend(this.options.fieldChoice, {
                entity: this.TYPE_CHOICE_ENTITY,
                data: _.object([this.TYPE_CHOICE_ENTITY], [this._createTypeChoiceEntityData()]),
                select2ResultsCallback: this.fieldChoiceResultsCallback.bind(this),
                applicableConditionsCallback: this.applicableConditionsCallback.bind(this)
            });

            ActivityConditionView.__super__.render.call(this);
            this._updateFieldChoice();

            var data = $.extend(true, {
                criterion: {
                    data: {
                        filterType: 'hasActivity',
                        activityType: {},
                        activityFieldName: ''
                    }
                }
            }, this.getValue());

            this._attachActivityFilter(data.criterion.data.filterType);
            this._attachTypeFilter(JSON.parse(this.options.listOption), data.criterion.data.activityType);

            var filterOptions = _.findWhere(this.options.filters, {
                type: 'datetime'
            });
            if (!filterOptions) {
                throw new Error('Cannot find filter "datetime"');
            }

            this._resolveDeferredRender();
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

        _attachTypeFilter: function(listOption, activityType) {
            var typeChoices = _.mapObject(listOption, _.property('label'));

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
            var oldEntity = this.$choiceInput.data('entity');
            var newEntity = this.TYPE_CHOICE_ENTITY;

            if (oldEntity !== newEntity) {
                this.getChoiceInputWidget().setValue('');
                this.$filterContainer.empty();
                if (this.filter) {
                    this.filter.reset();
                }
            }

            this.getChoiceInputWidget().updateData(newEntity, this.$fieldsLoader.data('fields'));
        },

        _updateFieldChoice: function() {
            var data = this.$fieldsLoader.data('fields');
            if (this.TYPE_CHOICE_ENTITY in data) {
                return;
            }
            data[this.TYPE_CHOICE_ENTITY] = this._createTypeChoiceEntityData();
            this.$fieldsLoader.data('fields', data);
            this.getChoiceInputWidget().updateData(this.TYPE_CHOICE_ENTITY, data);
        },

        fieldChoiceResultsCallback: function(results) {
            if (_.isEmpty(results)) {
                return results;
            }

            var fields = _.first(results).children;
            var activities = _.filter(fields, function(item) {
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

        _createTypeChoiceEntityData: function() {
            var fieldsIndex = {
                $createdAt: {
                    label: __('oro.activitylist.created_at.label'),
                    name: '$createdAt',
                    type: 'datetime',
                    entity: this.TYPE_CHOICE_ENTITY
                },
                $updatedAt: {
                    label: __('oro.activitylist.updated_at.label'),
                    name: '$updatedAt',
                    type: 'datetime',
                    entity: this.TYPE_CHOICE_ENTITY
                }
            };
            return {
                fields: _.values(fieldsIndex),
                fieldsIndex: fieldsIndex,
                label: '',
                name: this.TYPE_CHOICE_ENTITY,
                plural_label: ''
            };
        },

        _getFilterCriterion: function() {
            var filter = {
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
                    entityClassName: $(this.options.entitySelector).val(),
                    activityFieldName: this.getChoiceInputValue()
                }
            };
        },

        _getInitialChoiceInputValue: function() {
            var criterion = _.result(this.getValue(), 'criterion');
            if (criterion) {
                return _.result(criterion.data, 'activityFieldName');
            }
        },

        _getFilterValue: function() {
            var criterion = _.result(this.getValue(), 'criterion');
            if (criterion && criterion.data) {
                return _.result(criterion.data.filter, 'data');
            }
        },

        _collectValue: function() {
            var value = ActivityConditionView.__super__._collectValue.call(this);
            return _.omit(value, 'columnName');
        }
    });

    return ActivityConditionView;
});
