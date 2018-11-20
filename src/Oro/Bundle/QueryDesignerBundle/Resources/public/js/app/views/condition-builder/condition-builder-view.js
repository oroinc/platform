define(function(require) {
    'use strict';

    var ConditionBuilderView;
    var $ = require('jquery');
    var _ = require('underscore');
    var Backbone = require('backbone');
    var __ = require('orotranslation/js/translator');
    var tools = require('oroui/js/tools');
    var BaseView = require('oroui/js/app/views/base/view');
    var ConditionItemView = require('oroquerydesigner/js/app/views/condition-builder/condition-item-view');
    var ConditionOperatorView = require('oroquerydesigner/js/app/views/condition-builder/condition-operator-view');
    var ConditionsGroupView = require('oroquerydesigner/js/app/views/condition-builder/conditions-group-view');
    require('jquery-ui');

    /**
     * @typedef {ConditionBuilderView|ConditionItemView|ConditionsGroupView|ConditionOperatorView} ConditionView
     */

    ConditionBuilderView = BaseView.extend({
        CONDITION_GROUP_CLASS: 'conditions-group',
        CONDITION_ITEM_CLASS: 'condition-item',
        CONDITION_OPERATOR_CLASS: 'condition-operator',
        defaults: {
            sortable: {
                // see jquery-ui sortable's options
                placeholder: 'sortable-placeholder',
                items: '>[data-criteria]'
            },
            conditionsGroup: {
                items: '>.condition[data-criteria]',
                cursorAt: '10 10',
                cancel: 'a, input, .btn, select'
            },
            criteriaList: {
                helper: 'clone',
                cancel: '.disabled'
            },
            operations: ['AND', 'OR'],
            criteriaListSelector: '.criteria-list',
            conditionContainerSelector: '.condition-container',
            helperClass: 'ui-grabbing',
            validation: {
                'condition-item': {
                    NotBlank: {message: 'oro.query_designer.condition_builder.condition_item.not_blank'}
                },
                'conditions-group': {
                    NotBlank: {message: 'oro.query_designer.condition_builder.conditions_group.not_blank'}
                }
            }
        },

        currentDraggingElementHeight: 0,

        /**
         * @type {Object<string, View>}
         */
        criteriaModules: undefined,

        /**
         * @type {Array|null}
         */
        value: null,

        /**
         * @type {Object<string, ConditionView>}
         */
        conditions: undefined,

        autoRender: true,

        events: function() {
            var events = {};
            events['mousedown ' + this.options.criteriaListSelector] = '_onCriteriaListMousedown';
            return events;
        },

        constructor: function ConditionBuilderView(options) {
            this.options = this._prepareOptions(options);
            ConditionBuilderView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            this.conditions = {};
            this.criteriaModules = {};

            this.eventBus = Object.create(Backbone.Events);
            this.listenTo(this.eventBus, {
                // can not be done on `dispose` event, because the disposing condition element is in DOM yet
                'condition:closed': this._onConditionClose
            });

            _.extend(this, _.defaults(_.pick(options, 'value'), {
                value: []
            }));

            this.$criteriaList = this.$(this.options.criteriaListSelector);
            this.$conditionContainer = this.$(this.options.conditionContainerSelector);

            this._initCriteriaList();

            this._loadCriteriaModules();

            ConditionBuilderView.__super__.initialize.call(this, options);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            _.each(this.conditions, function(condition) {
                this.stopListening(condition);
            }, this);

            var props = ['criteriaModules', 'conditions', 'eventBus', 'value', '$criteriaList', '$content'];
            for (var i = 0; i < props.length; i++) {
                delete this[props[i]];
            }

            ConditionBuilderView.__super__.dispose.call(this);
        },

        render: function() {
            while (this.subviews.length) {
                this.removeSubview(_.last(this.subviews));
            }
            this.$conditionContainer.children('[data-role="condition-content"]').remove();

            ConditionBuilderView.__super__.render.call(this);
            this._renderRootConditionsGroup();

            this._deferredRender();
            $.when(this.criteriaModules).done(function() {
                this._renderValue(this.getValue());
                this._updateContainerClass();
                this._resolveDeferredRender();
            }.bind(this));

            return this;
        },

        _prepareOptions: function(options) {
            var opts = $.extend(true, {}, this.defaults, options);
            opts.conditionsGroup = $.extend({}, opts.sortable, opts.conditionsGroup, {
                helper: this._renderHelper.bind(this),
                start: this._onConditionsGroupGrab.bind(this),
                stop: this._onConditionsGroupDrop.bind(this),
                change: this._onCriteriaChange.bind(this),
                update: this._onStructureUpdate.bind(this),
                over: this._syncDropAreaOver.bind(this),
                out: this._syncDropAreaOut.bind(this),
                appendTo: opts.criteriaListSelector,
                connectWith: '.' + this.CONDITION_GROUP_CLASS
            });
            opts.criteriaList = $.extend({}, opts.sortable, opts.criteriaList, {
                start: this._onCriteriaGrab.bind(this),
                stop: this._onCriteriaDrop.bind(this),
                change: this._onCriteriaChange.bind(this),
                over: this._syncDropAreaOver.bind(this),
                out: this._syncDropAreaOut.bind(this),
                connectWith: '.' + this.CONDITION_GROUP_CLASS
            });
            return opts;
        },

        getValue: function() {
            return _.clone(this.value);
        },

        setValue: function(value) {
            if (!tools.isEqualsLoosely(value, this.value)) {
                this.value = value;
                this.render();
            }
        },

        _checkValueChange: function() {
            var value = this._collectValue();
            if (!tools.isEqualsLoosely(value, this.value)) {
                this.value = value;
                this.trigger('change', this.value);
            }
        },

        _collectValue: function() {
            return _.map(this.$content.find('>[data-condition-cid]'), function(elem) {
                return this.getConditionViewOfElement(this.$(elem)).getValue();
            }.bind(this));
        },

        /**
         * Enrolls condition view to the list for quick access by its cid
         *
         * @param {ConditionView} conditionView
         * @protected
         */
        _addConditionToRegistry: function(conditionView) {
            this.conditions[conditionView.cid] = conditionView;
            this.listenToOnce(conditionView, 'dispose', function(conditionView) {
                this.stopListening(conditionView);
                delete this.conditions[conditionView.cid];
            });
        },

        /**
         * Fetches registered condition new on base of element or its content element
         *
         * @param {Element} elem
         * @returns {ConditionView|undefined}
         */
        getConditionViewOfElement: function(elem) {
            var cid = $(elem)
                .filter('[data-role="condition-content"],[data-condition-cid]')
                .closest('[data-condition-cid]').data('conditionCid');
            return this.conditions[cid];
        },

        /**
         * Adds condition view to subviews list and subscribes on its change event
         *
         * @param {ConditionView} conditionView
         */
        assignConditionSubview: function(conditionView) {
            this.subview('condition:' + conditionView.cid, conditionView);
            this.listenTo(conditionView, {
                change: this._checkValueChange
            });
            this._checkValueChange();
        },

        /**
         * Removes condition view from subviews list and stops listenting it
         *
         * @param {ConditionView} conditionView
         */
        unassignConditionSubview: function(conditionView) {
            var name = 'condition:' + conditionView.cid;
            var index = _.indexOf(this.subviews, conditionView);
            if (index !== -1) {
                this.subviews.splice(index, 1);
            }
            delete this.subviewsByName[name];
            this.stopListening(conditionView, 'change');
            this._checkValueChange();
        },

        /**
         * Fetches criteria element from criteriaList by its name
         *
         * @param {string} criteria
         * @returns {jQuery.Element}
         */
        getCriteriaOrigin: function(criteria) {
            if (criteria === 'conditions-group-aggregated') {
                criteria = 'conditions-group';
            }
            var $criteria = this.$criteriaList.find('[data-criteria="' + criteria + '"]');
            return $criteria.data('origin') || $criteria;
        },

        /**
         * Enables/disables the criteria in the list of condition builder
         *
         * @param {string} criteriaName
         * @param {boolean} isEnabled
         */
        toggleCriteria: function(criteriaName, isEnabled) {
            this.$criteriaList.find('[data-criteria="' + criteriaName + '"]').toggleClass('disabled', !isEnabled);
        },

        /**
         * Applies options update for the criteria in the list of condition builder
         *
         * @param {string} criteriaName
         * @param {Object} optionsUpdate
         */
        updateCriteriaOptions: function(criteriaName, optionsUpdate) {
            var $criteria = this.$criteriaList.find('[data-criteria="' + criteriaName + '"]');
            _.extend($criteria.data('options'), optionsUpdate);
        },

        _initCriteriaList: function() {
            return this.$criteriaList.sortable(this.options.criteriaList);
        },

        /**
         * Collects modules definition in criteriaList and loads them, returns promise object
         *
         * @returns {JQueryPromise<T>}
         * @protected
         */
        _loadCriteriaModules: function() {
            var deferred = $.Deferred();
            var promise = this.criteriaModules = deferred.promise();
            // if some criteria requires addition modules, load them before initialization
            var modules = this.$criteriaList.find('[data-module]').map(function(i, elem) {
                return $(elem).data('module');
            }).get();
            tools.loadModules(_.object(modules, modules), function(modules) {
                this.criteriaModules = modules;
                deferred.resolve(modules);
            }, this);
            return promise;
        },

        /**
         * Fetches extra options for condition view from related criteria element of criteria list
         *
         * @param {string} criteria
         * @returns {{view: {Function}, viewOptions: {Object}}}
         */
        _getConditionItemViewExtraOptions: function(criteria) {
            var $criteria = this.getCriteriaOrigin(criteria);
            var moduleName = $criteria.data('module');
            return {
                view: this.criteriaModules[moduleName],
                viewOptions: $criteria.data('options')
            };
        },

        _renderRootConditionsGroup: function() {
            this.$conditionContainer.attr({
                'data-condition-cid': this.cid
            });
            this.$content = $('<ul class="conditions-group" data-role="condition-content"/>');
            this.$conditionContainer.append(this.$content);
            this._initConditionsGroup(this.$content);
            this.conditions[this.cid] = this;
        },

        _renderValue: function(value) {
            var lastValue = _.last(value);
            //  If the last value item is a group of aggregated condition item, specify its group type
            if (this._isGroupOfAggregatedConditionItems(lastValue)) {
                Object.defineProperty(lastValue, 'criteria', {value: 'conditions-group-aggregated'});
            }

            var subviews = this._createConditionGroupSubviews(value);
            var elements = _.map(subviews, function(view) {
                return view.el;
            });
            this.$content.append(elements);
            // all elements have to be added to DOM first before assigning subviews
            _.each(subviews, function(view) {
                this.assignConditionSubview(view);
            }, this);
            this.$conditionContainer.trigger('content:changed');
        },

        _renderCondition: function(criteria, value) {
            var condition;
            var validation = this.options.validation[criteria] || this.options.validation['condition-item'];
            if (['conditions-group', 'conditions-group-aggregated'].indexOf(criteria) !== -1) {
                condition = new ConditionsGroupView({
                    criteria: criteria,
                    value: value || [],
                    validation: validation,
                    eventBus: this.eventBus
                });
                this._addConditionToRegistry(condition);
                _.each(this._createConditionGroupSubviews(condition.value), function(view) {
                    condition.assignConditionSubview(view);
                });
                condition.render();
                this._initConditionsGroup(condition.$content);
            } else {
                condition = new ConditionItemView(_.extend({
                    autoRender: true,
                    criteria: criteria,
                    value: value || {},
                    validation: validation,
                    eventBus: this.eventBus
                }, this._getConditionItemViewExtraOptions(criteria)));
                this._addConditionToRegistry(condition);
            }
            return condition;
        },

        /**
         *
         * @param {Array.<string|Object|Array>} groupValue
         * @private
         */
        _createConditionGroupSubviews: function(groupValue) {
            return _.map(groupValue, function(value, index) {
                var criteria;
                if (typeof value === 'string') {
                    criteria = this._getCriteriaOfConditionValue(groupValue[index + 1]);
                    return this._createConditionOperatorView(criteria, value);
                } else {
                    criteria = this._getCriteriaOfConditionValue(value);
                    return this._renderCondition(criteria, value);
                }
            }, this);
        },

        _getCriteriaOfConditionValue: function(value) {
            var criteria;
            criteria = value.criteria || (_.isArray(value) ? 'conditions-group' : 'condition-item');
            return criteria;
        },

        /**
         *
         * @param {string} beforeCriteria
         * @param {string=} operation
         */
        _createConditionOperatorView: function(beforeCriteria, operation) {
            var operations = this.options.operations;
            if (beforeCriteria === 'conditions-group-aggregated') {
                operations = ['AND'];
            }

            var operatorView = new ConditionOperatorView({
                autoRender: true,
                tagName: 'li',
                label: __('oro.querydesigner.condition_operation'),
                className: this.CONDITION_OPERATOR_CLASS,
                buttonClass: 'btn btn-sm',
                operations: operations,
                selectedOperation: operation
            });
            this._addConditionToRegistry(operatorView);
            return operatorView;
        },

        _renderHelper: function(e, $condition) {
            var $criteria = this.getCriteriaOrigin($condition.data('criteria'));
            this.currentDraggingElementHeight = $condition.height();
            return $criteria.clone()
                .css({width: $criteria.outerWidth(), height: $criteria.outerHeight()})
                .addClass(this.options.helperClass);
        },

        _initConditionsGroup: function($group) {
            $group.sortable(this.options.conditionsGroup);
        },

        _onStructureUpdate: function(e, ui) {
            var group;
            var condition;

            if (ui.placeholder && ui.placeholder.hasClass('hide') ||
                ui.sender && !$.contains(this.el, ui.sender[0]) ||
                !this._isPlaceholderInValidPosition(ui.item, ui.item)
            ) {
                $(ui.sender || e.target).sortable('cancel');
                if (ui.item.data('clone')) {
                    ui.item.detach();
                }
            } else if (ui.sender && ui.sender.is(this.$criteriaList)) {
                // new condition
                var criteria = ui.item.data('criteria');
                if (criteria !== 'aggregated-condition-item' || this._getConditionsGroupAggregated()) {
                    // regular condition
                    condition = this._renderCondition(criteria);
                } else {
                    // first aggregated-condition has to be wrapped in own group
                    condition = this._renderCondition('conditions-group-aggregated', [{criteria: criteria}]);
                }
                group = this.getConditionViewOfElement(ui.item.parent());
                condition.$el.insertBefore(ui.item);
                group.assignConditionSubview(condition);
            } else if (!ui.sender) {
                // existing condition rearrange
                group = this.getConditionViewOfElement(ui.item.parent());
                condition = this.getConditionViewOfElement(ui.item);
                var oldGroup = this.getConditionViewOfElement(e.target);
                if (oldGroup !== group) {
                    oldGroup.unassignConditionSubview(condition);
                    group.assignConditionSubview(condition);
                }
            }

            this._updateOperators();
            this._updateContainerClass();
            this._checkValueChange();

            this.$conditionContainer.trigger('content:changed');
        },

        /**
         *
         * @param {ConditionView} closedConditionView
         */
        _onConditionClose: function(closedConditionView) {
            var parentConditionView = _.find(this.conditions, function(conditionView) {
                return conditionView.subviews.indexOf(closedConditionView) !== -1;
            });
            this._updateOperators();
            this._updateContainerClass();
            if (parentConditionView) {
                parentConditionView.unassignConditionSubview(closedConditionView);
            }
            this.$conditionContainer.trigger('content:changed');
        },

        _onCriteriaListMousedown: function() {
            $(':focus').blur();
        },

        _onCriteriaGrab: function(e, ui) {
            // create clone element just to remember place of item
            var $origin = ui.item;
            var $clone = $origin.clone();
            $origin.data('clone', $clone);
            $clone
                .data('origin', $origin)
                .removeAttr('style')
                .insertAfter($origin);
            ui.helper.addClass(this.options.helperClass);

            this.$conditionContainer.addClass('drag-start');
        },

        _onCriteriaDrop: function(e, ui) {
            // put item back instead of it's clone
            var $origin = ui.item;
            var $clone = $origin.data('clone');
            $clone.removeData('origin').replaceWith($origin.removeData('clone'));
            this.$conditionContainer.removeClass('drag-start drop-area-over');
        },

        _onConditionsGroupGrab: function(e, ui) {
            if (ui.item.is(':first-child')) {
                // hide following condition-operator
                ui.item.find('~.condition-operator:first').addClass('hide-operator');
                ui.item.parent().addClass('drag-start-from-first');
            } else {
                // hide leading condition-operator
                ui.item.prev('.condition-operator').addClass('hide-operator');
            }
            if (ui.placeholder.is(':last-child') /* placeholder is already added into DOM */) {
                ui.item.parent().addClass('drag-start-from-last');
            }

            this.$content.find('.sortable-placeholder').css({
                height: this.currentDraggingElementHeight
            });
        },

        _onConditionsGroupDrop: function(e, ui) {
            // cleanup styles
            this.$content.removeClass('drag-start-from-first drag-start-from-last');
            this.$content.find('.drag-start-from-first').removeClass('drag-start-from-first');
            this.$content.find('.drag-start-from-last').removeClass('drag-start-from-last');
            this.$content.find('.hide-operator').removeClass('hide-operator');
        },

        _onCriteriaChange: function(e, ui) {
            if (this._isPlaceholderInValidPosition(ui.item, ui.placeholder)) {
                this.$('.sortable-placeholder').removeClass('hide');
            } else {
                this.$('.sortable-placeholder').addClass('hide');
            }
        },

        _syncDropAreaOver: function(e, ui) {
            var hasPlaceholder = this.$content.find('.sortable-placeholder').length !== 0;

            this.$conditionContainer
                .toggleClass('drag-start', !hasPlaceholder)
                .toggleClass('drop-area-over', hasPlaceholder);
        },

        _syncDropAreaOut: function(e, ui) {
            var hasPlaceholder = this.$content.find('.sortable-placeholder').length !== 0;

            this.$conditionContainer
                .removeClass('drag-start')
                .toggleClass('drop-area-over', hasPlaceholder);
        },

        _isPlaceholderInValidPosition: function($condition, $placeholder) {
            var criteria = $condition.data('criteria');
            var groupAggregated = this._getConditionsGroupAggregated();
            var condition = this.getConditionViewOfElement($condition);
            var value = condition ? condition.getValue() : null;
            var isValid;

            if (!$.contains(this.el, $condition[0]) || !$.contains(this.el, $placeholder[0])) {
                return false;
            }

            switch (criteria) {
                case 'aggregated-condition-item':
                    // at the and of root condition (if group of aggregated items does not exist yet)
                    // or inside group of aggregated items
                    isValid = !groupAggregated && this.$content.find('>:last-child').is($placeholder) ||
                        groupAggregated && groupAggregated.$($placeholder).length;
                    break;
                case 'conditions-group':
                    isValid = _.isEmpty(value) || !groupAggregated ||
                        this._isGroupOfAggregatedConditionItems(value) && groupAggregated.$($placeholder).length ||
                        !this._isGroupOfAggregatedConditionItems(value) && !groupAggregated.$($placeholder).length;
                    break;
                default:
                    isValid = criteria !== 'conditions-group-aggregated' && (
                        !groupAggregated || !groupAggregated.$($placeholder).length &&
                        !this.$content.find('>:last-child').is($placeholder)
                    );
            }
            return isValid;
        },

        /**
         * Check if the value is a group of aggregated condition item
         *
         * @param {Array|Object|null} value
         * @returns {boolean}
         * @protected
         */
        _isGroupOfAggregatedConditionItems: function(value) {
            return _.isArray(value) && Boolean(_.findWhere(_.flatten(value), {criteria: 'aggregated-condition-item'}));
        },

        /**
         * @returns {ConditionView|undefined}
         * @protected
         */
        _getConditionsGroupAggregated: function() {
            return this.getConditionViewOfElement(this.$('[data-criteria="conditions-group-aggregated"]'));
        },

        _updateOperators: function() {
            var $conditions = this.$conditionContainer.find('.conditions-group>[data-condition-cid]');

            // remove operators for first items in groups
            var selector = '.%s:first-child, .%s:last-child, .%s+.%s'.replace(/%s/g, this.CONDITION_OPERATOR_CLASS);
            $conditions.filter(selector).each(function(i, elem) {
                var operator = this.getConditionViewOfElement(elem);
                var group = this.getConditionViewOfElement(operator.$el.parent());
                operator.dispose();
                group.unassignConditionSubview(operator);
            }.bind(this));

            // add condition operators where it is needed
            $conditions.filter('.condition:not(:first-child)').each(function(i, elem) {
                var condition = this.getConditionViewOfElement(elem);
                if (condition && !condition.$el.prev().is('.' + this.CONDITION_OPERATOR_CLASS)) {
                    var operator = this._createConditionOperatorView(condition.criteria);
                    condition.$el.before(operator.$el);
                    var group = this.getConditionViewOfElement(operator.$el.parent());
                    group.assignConditionSubview(operator);
                }
            }.bind(this));
        },

        _updateContainerClass: function() {
            this.$conditionContainer.toggleClass('empty', this.$content.is(':empty'));
        }
    });

    return ConditionBuilderView;
});
