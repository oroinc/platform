define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const FieldConditionView = require('oroquerydesigner/js/app/views/field-condition-view');

    const AggregatedFieldConditionView = FieldConditionView.extend({
        /**
         * @inheritdoc
         */
        constructor: function AggregatedFieldConditionView(options) {
            AggregatedFieldConditionView.__super__.constructor.call(this, options);
        },

        getDefaultOptions: function() {
            const defaultOptions = AggregatedFieldConditionView.__super__.getDefaultOptions.call(this);
            return _.extend({}, defaultOptions, {
                columnsCollection: null
            });
        },

        render: function() {
            const data = this.getValue();
            if (data && data.columnName && data.func) {
                const column = this._getColumnByNameAndFunc(data.columnName, data.func);
                if (column) {
                    this.$('.' + this.options.choiceInputClass).data('data', this._buildColumnDataItem(column));
                }
            }
            _.extend(this.options.fieldChoice, {
                select2ResultsCallback: this.fieldChoiceResultsCallback.bind(this),
                applicableConditionsCallback: this.applicableConditionsCallback.bind(this)
            });

            const select2Opts = this.options.fieldChoice.select2;
            if (select2Opts) {
                const templateString = select2Opts.formatSelectionTemplate ||
                    $(select2Opts.formatSelectionTemplateSelector).text();
                if (templateString) {
                    this.options.fieldChoice = _.extend({}, this.options.fieldChoice, {
                        select2: _.extend({}, select2Opts, {
                            formatSelectionTemplate: this._compileSelectionTpl(templateString)
                        })
                    });
                }
            }

            AggregatedFieldConditionView.__super__.render.call(this);

            return this;
        },

        onChoiceInputReady: function(choiceInputView) {
            AggregatedFieldConditionView.__super__.onChoiceInputReady.call(this, choiceInputView);

            this.listenTo(this.options.columnsCollection, {
                'remove': function(model) {
                    const name = this._getColumnName();
                    const funcName = this._getColumnFuncName();
                    const func = model.get('func');
                    if (model.get('name') === name && func && func.name === funcName) {
                        this._onRelatedColumnRemoved();
                    }
                },
                'change:func change:label': function(model) {
                    const name = this._getColumnName();
                    const funcName = this._getColumnFuncName();
                    const func = model.previous('func');
                    if (model.previous('name') === name && func && func.name === funcName) {
                        this._onRelatedColumnRemoved();
                    }
                }
            });
        },

        _onRelatedColumnRemoved: function() {
            this.setChoiceInputValue('').then(function() {
                this.trigger('close');
            }.bind(this));
        },

        _compileSelectionTpl: function(template) {
            const compiledTemplate = _.template(template);
            return data => {
                let result = compiledTemplate(data);
                const func = this._getCurrentFunc();
                if (func && func.name) {
                    result += ' (' + func.name + ')';
                }
                return result;
            };
        },

        applicableConditionsCallback: function(result, fieldId) {
            const returnType = _.result(this._getCurrentFunc(), 'return_type');
            if (returnType) {
                result.type = returnType;
            }

            return result;
        },

        fieldChoiceResultsCallback: function() {
            return _.map(
                this._getAggregatedColumns(),
                model => {
                    return this._buildColumnDataItem(model);
                }
            );
        },

        _getAggregatedColumns: function() {
            return _.filter(
                this.options.columnsCollection.models,
                _.compose(_.matcher({group_type: 'aggregates'}), _.property('func'), _.property('attributes'))
            );
        },

        _collectValue: function() {
            const value = AggregatedFieldConditionView.__super__._collectValue.call(this);
            if (!_.isEmpty(value)) {
                value.func = this._getCurrentFunc();
            }
            return value;
        },

        _buildColumnDataItem: function(model) {
            const func = model.get('func');
            return {
                id: model.get('name'),
                text: model.get('label'),
                func: (func && func.name ? func.name : null)
            };
        },

        _getColumnName: function() {
            return _.result(this.subview('choice-input').getData(), 'id');
        },

        _getColumnFuncName: function() {
            return _.result(this.subview('choice-input').getData(), 'func');
        },

        _getCurrentFunc: function() {
            const name = this._getColumnName();
            const funcName = this._getColumnFuncName();
            const column = _.find(this.options.columnsCollection.where({name: name}), function(column) {
                const func = column.get('func');
                return func && func.name === funcName;
            });
            if (_.isEmpty(column)) {
                return;
            }

            return column.get('func');
        },

        _getColumnByNameAndFunc: function(name, func) {
            if (!func) {
                return;
            }

            return _.find(this.options.columnsCollection.where({name: name}), function(column) {
                const func = column.get('func');
                return func && func.name === func.name;
            });
        },

        _getFilterCriterion: function() {
            const criterion = AggregatedFieldConditionView.__super__._getFilterCriterion.call(this);
            $.extend(true, criterion, {
                data: {
                    params: {
                        filter_by_having: true
                    }
                }
            });

            return criterion;
        },

        _hasEmptyFilter: function() {
            const isEmptyFilter = AggregatedFieldConditionView.__super__._hasEmptyFilter.call(this);
            return isEmptyFilter || _.isEmpty(this._getCurrentFunc());
        }
    });

    return AggregatedFieldConditionView;
});
