/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'jquery-ui', 'oroui/js/dropdown-select', './compare-field'], function ($, _) {
    'use strict';

    /**
     * Conditions group widget
     */
    $.widget('oroquerydesigner.conditionsGroup', {
        options: {
            sortable: {
                // see jquery-ui sortable's options
                containment: '#container',
                placeholder: 'sortable-placeholder',
                items: '>[data-criteria]',
                connectWith: 'ul.conditions-group'
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
            compareFieldsHTML: '<div class="field-filter" />',
            conditionsGroupHTML: '<ul class="conditions-group" />',
            validation: {
                'compare-fields': {NotBlank: {message: 'This condition should not be blank.'}},
                'conditions-group': {NotBlank: {message: 'This group should not be blank.'}}
            }
        },

        _create: function () {
            this._prepareOptions();
            this._initCriteriaList();
            this._initConditionBuilder();
            this._updateOperators();
            this._on({
                closed: this._onConditionClose
            });
            this.element
                .on('change', '.operator', $.proxy(this._onChangeOperator, this));
        },

        _prepareOptions: function () {
            var opts = this.options;
            opts.conditionsGroup = $.extend({}, opts.sortable, opts.conditionsGroup);
            opts.conditionsGroup.appendTo = opts.criteriaListSelector;
            opts.conditionsGroup.helper = $.proxy(this._createHelper, this);
            opts.conditionsGroup.update = $.proxy(this._onUpdate, this);
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
            $(this.options.criteriaListSelector).sortable(this.options.criteriaList);
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
            // on change update group's value
            $group.on('changed', function () {
                var values = [];
                $group.find('>[data-criteria]>[data-value]').each(function () {
                    var $el = $(this),
                        value = $el.data('value');
                    values.push(value);
                    if ($.type(value) !== 'string') {
                        // means, value is not an operator
                        $el.parent().find('>input[name^=condition_group_]').prop('checked', !_.isEmpty(value));
                    }
                });
                $group.data('value', values);
            });
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
            var $criteria = $(this.options.criteriaListSelector).find('[data-criteria="' + criteria + '"]');
            return $criteria.data('origin') || $criteria;
        },

        _createCondition: function (criteria, value) {
            var $content, $condition, $criteria, options, rule;
            if (!criteria) {
                // if criteria is not passed, define it from value
                criteria = $.isArray(value) ? 'conditions-group' : 'compare-fields';
            }
            $criteria = this._getCriteriaOrigin(criteria);
            options = _.omit($criteria.data(), ['clone', 'sortableItem', 'criteria']);

            switch (criteria) {
            case 'compare-fields':
                $content = this._createConditionContent(this.options.compareFieldsHTML, value || {});
                $content.compareField(options);
                break;
            case 'conditions-group':
                $content = this._createConditionContent(this.options.conditionsGroupHTML, value || []);
                this._initConditionsGroup($content);
                break;
            }

            $condition = $(this.options.conditionHTML)
                .attr('data-criteria', criteria)
                .prepend($content)
                .prepend('<a class="close" data-dismiss="alert" href="#">&times;</a>');

            rule = this.options.validation[criteria];
            if (rule) {
                $condition.append(this._createValidationInput(rule, $content.data('value')));
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

        _createValidationInput: function (rule, value) {
            return $('<input class="select2-focusser select2-offscreen" type="checkbox"/>')
                .prop('checked', !_.isEmpty(value))
                .attr('name', 'condition_group_' + Date.now())
                .data('validation', rule);
        },

        _createHelper: function (e, $el) {
            var $criteria = this._getCriteriaOrigin($el.data('criteria'));
            return $criteria.clone()
                .css({width: $criteria.outerWidth(), height: $criteria.outerHeight()})
                .addClass(this.options.helperClass);
        },

        _onUpdate: function (e, ui) {
            var $condition;
            // new condition
            if (ui.sender && ui.sender.is(this.options.criteriaListSelector)) {
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
            _.delay($.proxy(function () {
                this._updateOperators();
                $group.trigger('changed');
            }, this), 1);
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
});
