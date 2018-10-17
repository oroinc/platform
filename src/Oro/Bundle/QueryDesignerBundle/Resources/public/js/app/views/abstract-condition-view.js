define(function(require) {
    'use strict';

    var AbstractConditionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var conditionTemplate = require('tpl!oroquerydesigner/templates/condition.html');
    var BaseView = require('oroui/js/app/views/base/view');

    AbstractConditionView = BaseView.extend({
        template: conditionTemplate,

        /**
         * @type {Object}
         */
        value: undefined,

        getDefaultOptions: function() {
            return {
                choiceInputClass: 'select',
                filters: [],
                filterContainerClass: 'active-filter'
            };
        },

        events: function() {
            var events = {};
            events['change .' + this.options.filterContainerClass] = '_onFilterChange';
            return events;
        },

        constructor: function AbstractConditionView(options) {
            this.options = _.defaults({}, options, this.getDefaultOptions());
            _.extend(this, _.pick(options, 'value'));
            AbstractConditionView.__super__.constructor.call(this, options);
        },

        getTemplateData: function() {
            var data = AbstractConditionView.__super__.getTemplateData.call(this);
            _.extend(data, _.pick(this.options, ['choiceInputClass', 'filterContainerClass']));
            return data;
        },

        render: function() {
            var choiceInputView = this.subview('choice-input');
            if (choiceInputView) {
                this.stopListening(choiceInputView);
                this.removeSubview('choice-input');
            }
            AbstractConditionView.__super__.render.call(this);

            this.$choiceInput = this.$('.' + this.options.choiceInputClass);
            this._deferredRender();
            this.initChoiceInputView().then(this.onChoiceInputReady.bind(this));

            return this;
        },

        initControls: function() {
            // all controls are defined by subviews
        },

        onChoiceInputReady: function(choiceInputView) {
            this.subview('choice-input', choiceInputView);
            this.listenTo(choiceInputView, 'change', this._onChoiceInputChanged);
            this.$filterContainer = this.$('.' + this.options.filterContainerClass);

            var choiceInputValue = this._getInitialChoiceInputValue();

            if (choiceInputValue) {
                this.setChoiceInputValue(choiceInputValue);
                this.once('filter-appended', function() {
                    this._resolveDeferredRender();
                }.bind(this));
                this._renderFilter(choiceInputValue);
            } else {
                this._resolveDeferredRender();
            }
        },

        _onChoiceInputChanged: function(selectedItem) {
            var choiceInputValue = this._getInitialChoiceInputValue();
            if (!selectedItem) {
                this._removeFilter();
            } else if (choiceInputValue !== selectedItem.id) {
                $(':focus').blur();
                // reset current value on field change
                this.setValue({});
                this._renderFilter(selectedItem.id);
            }
        },

        _onFilterChange: function(e) {
            if (this.filter && $.contains(this.filter.el, e.target) && !$(e.target).is('[data-fake-front-field]')) {
                this.filter.applyValue();
            }
        },

        _getApplicableFilterId: function(conditions) {
            var filterId = null;
            var matchedBy = null;
            var self = this;

            if (!_.isUndefined(conditions.filter)) {
                // the criteria parameter represents a filter
                return conditions.filter;
            }

            _.each(this.options.filters, function(filter, id) {
                var matched;

                if (!_.isEmpty(filter.applicable)) {
                    // check if a filter conforms the given criteria
                    matched = self._matchApplicable(filter.applicable, conditions);
                    if (matched && (
                        _.isNull(matchedBy) ||
                            // new rule is more exact
                            _.size(matchedBy) < _.size(matched) ||
                            // 'type' rule is most low level one, so any other rule can override it
                            (_.size(matchedBy) === 1 && _.has(matchedBy, 'type'))
                    )) {
                        matchedBy = matched;
                        filterId = id;
                    }
                }
            });

            return filterId;
        },

        _matchApplicable: function(applicable, criteria) {
            var hierarchy = this.options.hierarchy[criteria.entity] || [];
            return _.find(applicable, function(item) {
                return _.every(item, function(value, key) {
                    if (key === 'entity' && hierarchy.length) {
                        return _.indexOf(hierarchy, criteria[key]);
                    }
                    return criteria[key] === value;
                });
            });
        },

        _getFilterValue: function() {
            var criterion = _.result(this.getValue(), 'criterion');
            return _.result(criterion, 'data');
        },

        _appendFilter: function(filter) {
            var filterValue = this._getFilterValue();

            if (filterValue) {
                filter.value = filterValue;
            }

            if (this.filter) {
                this.stopListening(this.filter);
            }

            filter.render();

            this.$filterContainer.empty().append(filter.$el);
            this.filter = filter;
            this.listenTo(this.filter, 'update typeChange', this._onUpdate);
            this.trigger('filter-appended');
            this._onUpdate();
        },

        _removeFilter: function() {
            if (this.filter) {
                this.stopListening(this.filter);
                this.filter.dispose();
                this.$filterContainer.empty();
                delete this.filter;
                this.trigger('filter-removed');
            }
        },

        _onUpdate: function() {
            var value = this._collectValue();
            this.setValue(value);
        },

        _collectValue: function() {
            var value = {};

            if (!this._hasEmptyFilter()) {
                value = {
                    columnName: this.getColumnName(),
                    criterion: this._getFilterCriterion()
                };
            }
            return value;
        },

        _getFilterCriterion: function() {
            var data = this.filter.getValue();

            if (this.filter.filterParams) {
                data.params = this.filter.filterParams;
            }

            return {
                filter: this.filter.name,
                data: data
            };
        },

        _hasEmptyFilter: function() {
            return !this.filter || this.filter.isEmptyValue();
        },

        _getInitialChoiceInputValue: function() {
            return _.result(this.getValue(), 'columnName');
        },

        setChoiceInputValue: function(name) {
            var deferred = $.Deferred();
            var columnName = _.result(this.getValue(), 'columnName');
            if (columnName !== name) {
                this.once('filter-appended filter-removed', function() {
                    deferred.resolve();
                });
            } else {
                deferred.resolve();
            }
            this._setChoiceInputValue(name);
            return deferred.promise();
        },

        _setChoiceInputValue: function(value) {
            this.subview('choice-input').setValue(value);
        },

        getValue: function() {
            return $.extend(true, {}, this.value);
        },

        setValue: function(value) {
            this.value = value;
            this.trigger('change', value);
        },

        getChoiceInputValue: function() {
            return this.subview('choice-input').getValue();
        },

        getColumnName: function() {
            return this.getChoiceInputValue();
        },

        /**
         * Inits particular view on $choiceInput element
         *
         * @return {Promise.<Backbone.View>}
         * @abstract
         */
        initChoiceInputView: function() {
            throw new Error('method `initChoiceInputView` should be implemented in a descendant');
        },

        /**
         * Finds filter constructor, prepares its options, creates and appends it to the view.
         *
         * @param {String} fieldId
         * @abstract
         * @protected
         */
        _renderFilter: function(fieldId) {
            throw new Error('method `_renderFilter` should be implemented in a descendant');
        }
    });

    return AbstractConditionView;
});
