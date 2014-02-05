/*global define, require*/
/*jslint nomen: true*/
define(['jquery', 'jquery-ui', 'oroui/js/dropdown-select'], function ($) {
    'use strict';

    /**
     * Conditions group widget
     */
    $.widget('oroquerydesigner.conditionBuilder', {
        options: {
            sortable: {
                // see jquery-ui sortable's options
                placeholder: 'sortable-placeholder',
                items: '>[data-criteria]',
                connectWith: '[data-criteria=conditions-group]'
            },
            conditionsGroup: {
                items: '>.condition[data-criteria]',
                cursorAt: "10 10",
                cancel: 'a, input, .btn, select'
            },
            criteriaList: {
                helper: 'clone',
                cancel: '.disabled'
            },
            operations: ['AND', 'OR'],
            criteriaListSelector: '#filter-criteria-list',
            sourceValueSelector: '',
            helperClass: 'ui-grabbing',
            conditionHTML: '<li class="condition controls" />',
            conditionItemHTML: '<div class="condition-item" />',
            conditionsGroupHTML: '<ul class="conditions-group" />',
            validation: {
                'condition-item': {
                    NotBlank: {message: 'oro.query_designer.condition_builder.condition_item.not_blank'}
                },
                'conditions-group': {
                    NotBlank: {message: 'oro.query_designer.condition_builder.conditions_group.not_blank'}
                }
            }
        },

        _create: function () {
            var modules;
            this.$criteriaList = $(this.options.criteriaListSelector);
            this._prepareOptions();

            // if some criteria requires addition modules, load them before initialization
            modules = this.$criteriaList.find('[data-module]').map(function () {
                return $(this).data('module');
            }).get();

            if (modules.length) {
                require(modules, $.proxy(this._initControl(), this));
            } else {
                this._initControl();
            }
        },

        _initControl: function () {
            this._initCriteriaList();
            this._initConditionBuilder();
            this._updateOperators();
            this._on({
                closed: this._onConditionClose
            });
            this.element
                .on('change', '.operator', $.proxy(this._onChangeOperator, this));
        },

        _getCreateOptions: function () {
            // makes a deep copy of default options
            return $.extend(true, {}, this.options);
        },

        _prepareOptions: function () {
            var opts = this.options;
            opts.conditionsGroup = $.extend({}, opts.sortable, opts.conditionsGroup);
            opts.conditionsGroup.appendTo = opts.criteriaListSelector;
            opts.conditionsGroup.helper = $.proxy(this._createHelper, this);
            opts.conditionsGroup.update = $.proxy(this._onHierarchyChange, this);
            opts.criteriaList = $.extend({}, opts.sortable, opts.criteriaList);
            opts.criteriaList.start = $.proxy(this._onCriteriaGrab, this);
            opts.criteriaList.stop = $.proxy(this._onCriteriaDrop, this);
        },

        getValue: function () {
            return this.$rootCondition ? this.$rootCondition.data('value') : [];
        },

        setValue: function (value) {
            value = value || [];
            this._createConditionContent(this.$rootCondition.empty(), value);
            this._setSourceValue(value);
        },

        _initCriteriaList: function () {
            this.$criteriaList
                .sortable(this.options.criteriaList)
                .on('mousedown', function () {
                    $(':focus').blur();
                });
        },

        _initConditionBuilder: function () {
            var $content,
                $root = this.element,
                sortableConnectWith = this.options.sortable.connectWith;
            if (!$root.is(sortableConnectWith)) {
                $root = $root.find(sortableConnectWith);
                if (!$root.length) {
                    $root = $(this.options.conditionsGroupHTML).appendTo(this.element);
                }
            }

            $content = this._createConditionContent($root, this._getSourceValue());
            this._initConditionsGroup($content);

            $root.on('changed', $.proxy(this._onChanged, this));

            this.$rootCondition = $root;
        },

        _initConditionsGroup: function ($group) {
            // make the group sortable
            $group.sortable(this.options.conditionsGroup);

            // handle condition-item value change
            $group.on('changed', '>[data-criteria]>[data-value]:not(.operator)', function () {
                var $content = $(this),
                    $condition = $content.parent(),
                    criteria = $condition.data('criteria'),
                    hasValue = !$.isEmptyObject($content.data('value'));
                // update validation checkbox if condition 'has/has not' value
                $condition.find('>input[name^=condition_item_]').prop('checked', hasValue);
                // if it's value of condition with not default criteria, mixin it's name into value
                if (hasValue && $.inArray(criteria, ['conditions-group', 'condition-item']) === -1) {
                    $.extend($content.data('value'), {criteria: criteria});
                }
            });

            // on change update group's value
            $group.on('changed', function () {
                var values = $group.find('>[data-criteria]>[data-value]').map(function () {
                    return $(this).data('value');
                }).get();
                $group.data('value', values);
            });

            $group.attr('data-criteria', 'conditions-group');
        },

        _onCriteriaGrab: function (e, ui) {
            // create clone element just to remember place of item
            var $origin = ui.item,
                $clone = $origin.clone();
            $origin.data('clone', $clone);
            $clone.data('origin', $origin).removeAttr('style').insertAfter($origin);
            ui.helper.addClass(this.options.helperClass);
        },

        _onCriteriaDrop: function (e, ui) {
            // put item back instead of it's clone
            var $origin = ui.item,
                $clone = $origin.data('clone');
            $clone.removeData('origin').replaceWith($origin.removeData('clone'));
        },

        _getCriteriaOrigin: function (criteria) {
            var $criteria = this.$criteriaList.find('[data-criteria="' + criteria + '"]');
            return $criteria.data('origin') || $criteria;
        },

        _createCondition: function (criteria, value) {
            var $content, $condition, $criteria, $validationInput, widgetOptions, widgetName;
            if (!criteria) {
                // if criteria is not passed, define it from value
                criteria = $.isArray(value) ? 'conditions-group' : (value.criteria || 'condition-item');
            }

            if (criteria === 'conditions-group') {
                $content = this._createConditionContent(this.options.conditionsGroupHTML, value || []);
                this._initConditionsGroup($content);
            } else {
                $content = this._createConditionContent(this.options.conditionItemHTML, value || {});
                $criteria = this._getCriteriaOrigin(criteria);
                widgetOptions = $criteria.data('options') || {};
                widgetName = $criteria.data('widget');
                if ($.isFunction($content[widgetName])) {
                    $content[widgetName](widgetOptions);
                }
            }

            $condition = $(this.options.conditionHTML)
                .attr('data-criteria', criteria)
                .prepend($content)
                .prepend('<a class="close" data-dismiss="alert" href="#">&times;</a>');

            $validationInput = this._createValidationInput(criteria, $content.data('value'));
            if ($validationInput) {
                $condition.append($validationInput);
            }

            return $condition;
        },

        _createConditionContent: function (html, value) {
            var operation, self = this,
                $content = $(html);
            if ($.isArray(value)) {
                // build sub-conditions, if value is array
                operation = null;
                $.each(value, function (i, val) {
                    var $condition;
                    if ($.type(val) === 'string') {
                        operation = val;
                    } else {
                        $condition = self._createCondition(null, val);
                        if (operation) {
                            self._initConditionOperation($condition, operation);
                            operation = null;
                        }
                        $content.append($condition);
                    }
                });
            }
            return $content
                .attr('data-value', '')
                .data('value', value);
        },

        _createValidationInput: function (criteria, value) {
            var $input, validation = this.options.validation,
                rule = validation[criteria] || validation['condition-item'];
            if (rule) {
                $input = $('<input class="select2-focusser select2-offscreen" type="checkbox"/>')
                    .prop('checked', !$.isEmptyObject(value))
                    .attr('name', 'condition_item_' + Date.now())
                    .data('validation', rule);
            }
            return $input;
        },

        _createHelper: function (e, $el) {
            var $criteria = this._getCriteriaOrigin($el.data('criteria'));
            return $criteria.clone()
                .css({width: $criteria.outerWidth(), height: $criteria.outerHeight()})
                .addClass(this.options.helperClass);
        },

        _onHierarchyChange: function (e, ui) {
            var $condition;
            // new condition
            if (ui.sender && ui.sender.is(this.$criteriaList)) {
                $condition = this._createCondition(ui.item.data('criteria'));
                $condition.insertBefore(ui.item);
                $condition.trigger('changed');
            }
            this._updateOperators();
        },

        _updateOperators: function () {
            var self = this,
                options = this.options.conditionsGroup,
                $conditions = this.element.find(options.connectWith + options.items);
            // remove operators for first items in groups
            $conditions.filter(':first-child')
                .find('>.operator').each(function () {
                    var $operator = $(this),
                        $condition = $operator.parent();
                    $operator.remove();
                    $condition.trigger('changed');
                });
            // add operators to proper conditions
            $conditions.filter(':not(:first-child)').not(':has(>.operator)')
                .each(function () {
                    self._initConditionOperation(this);
                }).trigger('changed');
        },

        _initConditionOperation: function ($condition, operation) {
            operation = operation || this.options.operations[0] || '';
            $('<div class="operator"/>')
                .attr('data-value', '')
                .data('value', operation)
                .prependTo($condition)
                .dropdownSelect({
                    buttonClass: 'btn btn-mini',
                    options: this.options.operations,
                    selected: operation
                });
        },

        _onChangeOperator: function (e) {
            $(e.target)
                .data('value', e.value)
                .trigger('changed');
        },

        _onConditionClose: function (e) {
            var $group = $(e.target).parent();
            this._delay(function () {
                this._updateOperators();
                $group.trigger('changed');
            }, 1);
        },

        _onChanged: function () {
            this._setSourceValue(this.$rootCondition.data('value'));
        },

        _setSourceValue: function (value) {
            if (this.options.sourceValueSelector) {
                $(this.options.sourceValueSelector).val(JSON.stringify(value));
            }
        },

        _getSourceValue: function () {
            var value;
            if (this.options.sourceValueSelector) {
                value = $(this.options.sourceValueSelector).val();
            }
            return value ? JSON.parse(value) : [];
        }
    });

    return $;
});
