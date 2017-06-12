define(['jquery', 'underscore', 'orotranslation/js/translator', 'jquery-ui',
    'oroui/js/dropdown-select'], function($, _, __) {
    'use strict';

    /**
     * Condition builder widget
     */
    $.widget('oroquerydesigner.conditionBuilder', {
        options: {
            sortable: {
                // see jquery-ui sortable's options
                placeholder: 'sortable-placeholder',
                items: '>[data-criteria]',
                connectWith: '.conditions-group'
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

        currentDraggingElementHeight: 0,

        _create: function() {
            var modules;
            this.$criteriaList = $(this.options.criteriaListSelector);
            this._prepareOptions();

            this._initCriteriaList();
            this._initConditionBuilder();

            this._on({
                'change .operator': '_onChangeOperator',
                'click .close': '_onConditionClose'
            });

            // if some criteria requires addition modules, load them before initialization
            modules = this.$criteriaList.find('[data-module]').map(function() {
                return $(this).data('module');
            }).get();

            if (modules.length) {
                require(modules, $.proxy(this._initControl, this));
            } else {
                this._initControl();
            }
        },

        _getCreateOptions: function() {
            // makes a deep copy of default options
            return $.extend(true, {}, this.options);
        },

        _prepareOptions: function() {
            var opts = this.options;
            opts.conditionsGroup = $.extend({}, opts.sortable, opts.conditionsGroup);
            opts.conditionsGroup.appendTo = opts.criteriaListSelector;
            opts.conditionsGroup.helper = $.proxy(this._createHelper, this);
            opts.conditionsGroup.update = $.proxy(this._onHierarchyChange, this);
            opts.conditionsGroup.over = $.proxy(this.syncDropAreaOver, this);
            opts.conditionsGroup.out = $.proxy(this.syncDropAreaOver, this);
            opts.criteriaList = $.extend({}, opts.sortable, opts.criteriaList);
            opts.criteriaList.start = $.proxy(this._onCriteriaGrab, this);
            opts.criteriaList.stop = $.proxy(this._onCriteriaDrop, this);
            opts.criteriaList.change = $.proxy(this._onCriteriaChange, this);

            opts.conditionsGroup.start = $.proxy(this._onConditionsGroupGrab, this);
            opts.conditionsGroup.stop = $.proxy(this._onConditionsGroupDrop, this);
            opts.criteriaList.over = $.proxy(this.syncDropAreaOver, this);
            opts.criteriaList.out = $.proxy(this.syncDropAreaOver, this);
        },

        getValue: function() {
            return this.$rootCondition.data('value') || [];
        },

        setValue: function(value) {
            value = value || [];
            if (this.$rootCondition.data('initialized')) {
                this._createConditionContent(this.$rootCondition.empty(), value);
            } else {
                this.$rootCondition.data('value', value);
            }
            this.$rootCondition.trigger('changed');
        },

        _initCriteriaList: function() {
            this.$criteriaList
                .sortable(this.options.criteriaList);
            this._on(this.$criteriaList, {
                mousedown: function() {
                    $(':focus').blur();
                }
            });
        },

        _initConditionBuilder: function() {
            var $root = this.element;
            var sortableConnectWith = this.options.sortable.connectWith;
            if (!$root.is(sortableConnectWith)) {
                $root = $root.find(sortableConnectWith);
                if (!$root.length) {
                    $root = $(this.options.conditionsGroupHTML).appendTo(this.element);
                }
            }

            $root.data('value', this._getSourceValue());
            this._on($root, {
                changed: '_onChanged'
            });
            this.$rootCondition = $root;
        },

        _initControl: function() {
            var $content = this._createConditionContent(this.$rootCondition, this.$rootCondition.data('value'));
            this._initConditionsGroup($content);
            this._updateOperators();
            this.$rootCondition.data('initialized', true);
        },

        _initConditionsGroup: function($group) {
            // make the group sortable
            $group.sortable(this.options.conditionsGroup);

            this._on($group, {
                // handle condition-item value change
                'changed >[data-criteria]>[data-value]:not(.operator)': function(e) {
                    var $content = $(e.currentTarget);
                    var $condition = $content.parent();
                    var criteria = $condition.data('criteria');
                    var hasValue = !$.isEmptyObject($content.data('value'));
                    // update validation checkbox if condition 'has/has not' value
                    $condition.find('>input[name^=condition_item_]').prop('checked', hasValue);
                    // if it's value of condition with not default criteria, mixin it's name into value
                    if (hasValue && $.inArray(criteria, ['conditions-group', 'condition-item']) === -1) {
                        $.extend($content.data('value'), {criteria: criteria});
                    }
                },
                // on change update group's value
                changed: function() {
                    var values = [];
                    $group.find('>[data-criteria]>[data-value]').each(function() {
                        values.push($(this).data('value'));
                    });
                    $group.data('value', values);
                }
            });
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

            // createDropAreaMarker
            this.$rootCondition.parent().prepend('<div class="drop-area-marker"><span>' +
                __('Drop condition here') +
                '</span></div>');
            /**
             * sum of heights of :before,:after condition group pseudo elements
             */
            var SPACERS_PSEUDO_ELEMENTS_HEIGHT = 20;
            this.$rootCondition
                .parent()
                .find('.drop-area-marker')
                // please do not replace to $smth.height(value) call because of bugs
                .css({
                    height: this.$rootCondition.height() - SPACERS_PSEUDO_ELEMENTS_HEIGHT
                });
        },

        _onCriteriaDrop: function(e, ui) {
            // put item back instead of it's clone
            var $origin = ui.item;
            var $clone = $origin.data('clone');
            $clone.removeData('origin').replaceWith($origin.removeData('clone'));
            this.$rootCondition.parent().find('.drop-area-marker').remove();
        },

        _onConditionsGroupGrab: function(e, ui) {
            var index = _.indexOf(ui.item[0].parentNode.children, ui.item[0]);
            if (index === 0) {
                ui.item.parent().addClass('drag-start-from-first');
                // second is placeholder
                // third is element we need
                if (ui.item[0].parentNode.children.length > 2) {
                    $(ui.item[0].parentNode.children[2]).addClass('hide-operator');
                }
            }
            if (index === ui.item[0].parentNode.children.length - 2 /* placeholder is already added into DOM*/) {
                ui.item.parent().addClass('drag-start-from-last');
            }

            this.$rootCondition.find('.sortable-placeholder').css({
                'height': this.currentDraggingElementHeight
            });
        },

        _onConditionsGroupDrop: function(e, ui) {
            // cleanup
            this.$rootCondition.removeClass('drag-start-from-first drag-start-from-last');
            this.$rootCondition.find('.drag-start-from-first').removeClass('drag-start-from-first');
            this.$rootCondition.find('.drag-start-from-last').removeClass('drag-start-from-last');
            this.$rootCondition.find('.hide-operator').removeClass('hide-operator');
        },

        _onCriteriaChange: function(e, ui) {
            if (this._isPlaceholderInValidPosition(e, ui)) {
                this.element.find('.sortable-placeholder').removeClass('hide');
            } else {
                this.element.find('.sortable-placeholder').addClass('hide');
            }
        },

        syncDropAreaOver: function(e, ui) {
            this.$rootCondition
                .parent()
                .toggleClass('drop-area-over', this.$rootCondition.find('.sortable-placeholder').length !== 0);
        },

        _isPlaceholderInValidPosition: function(e, ui) {
            if (ui.item.data('criteria') === 'aggregated-condition-item') {
                if (
                    ui.placeholder.closest('[condition-type=aggregated-condition-item]').length ||
                    (ui.placeholder.is(':last-child') &&
                        !this.element.find('[condition-type=aggregated-condition-item]').length)
                ) {
                    return true;
                }

                return false;
            } else if (ui.item.data('criteria') !== 'conditions-group' &&
                ui.placeholder.closest('[condition-type=aggregated-condition-item]').length
            ) {
                return false;
            }

            return !ui.placeholder.prev('[condition-type=aggregated-condition-item]').length;
        },

        _updateRootAggregatedCondition: function($condition) {
            $condition
                .attr('condition-type', 'aggregated-condition-item')
                .find('>.operator [data-value=OR]').parent('li').remove();
        },

        _getCriteriaOrigin: function(criteria) {
            var $criteria = this.$criteriaList.find('[data-criteria="' + criteria + '"]');
            return $criteria.data('origin') || $criteria;
        },

        _createCondition: function(criteria, value) {
            var $content;
            var  $condition;
            var  $criteria;
            var  $validationInput;
            var  widgetOptions;
            var  widgetName;
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
                .prepend('<a class="close" href="#">&times;</a>');

            $validationInput = this._createValidationInput(criteria, $content.data('value'));
            if ($validationInput) {
                $condition.append($validationInput);
            }

            return $condition;
        },

        _createConditionContent: function(html, value) {
            var operation;
            var self = this;
            var $content = $(html);
            if ($.isArray(value)) {
                // build sub-conditions, if value is array
                operation = null;
                $.each(value, function(i, val) {
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

        _createValidationInput: function(criteria, value) {
            var $input;
            var validation = this.options.validation;
            var rule = validation[criteria] || validation['condition-item'];
            if (rule) {
                $input = $('<input class="select2-focusser select2-offscreen" type="checkbox"/>')
                    .prop('checked', !$.isEmptyObject(value))
                    .attr('name', 'condition_item_' + Date.now())
                    .data('validation', rule);
            }
            return $input;
        },

        _createHelper: function(e, $el) {
            var $criteria = this._getCriteriaOrigin($el.data('criteria'));
            this.currentDraggingElementHeight = $el.find('.condition-item').outerHeight();
            return $criteria.clone()
                .css({width: $criteria.outerWidth(), height: $criteria.outerHeight()})
                .addClass(this.options.helperClass);
        },

        _onHierarchyChange: function(e, ui) {
            var $condition;
            // new condition
            if (ui.sender && ui.sender.is(this.$criteriaList)) {
                if (ui.placeholder && ui.placeholder.hasClass('hide')) {
                    return;
                }

                var criteria = ui.item.data('criteria');
                if (criteria === 'aggregated-condition-item' &&
                    !this.element.find('[condition-type=aggregated-condition-item]').length
                ) {
                    var $conditionsGroup = this._createCondition('conditions-group');
                    $conditionsGroup.insertBefore(ui.item);
                    $condition = this._createCondition(ui.item.data('criteria'));
                    $conditionsGroup.find('.conditions-group').append($condition);
                } else {
                    $condition = this._createCondition(criteria);
                    $condition.insertBefore(ui.item);
                }
            } else {
                $condition = ui.item;
            }
            $condition.trigger('changed');
            this._updateOperators();
        },

        _updateOperators: function() {
            var self = this;
            var options = this.options.conditionsGroup;
            var $conditions = this.element.find(options.connectWith + options.items);
            // remove operators for first items in groups
            $conditions.filter(':first-child')
                .find('>.operator').each(function() {
                    var $operator = $(this);
                    var $condition = $operator.parent();
                    $operator.remove();
                    $condition.trigger('changed');
                });
            // add operators to proper conditions
            $conditions.filter(':not(:first-child)').not(':has(>.operator)')
                .each(function() {
                    self._initConditionOperation($(this));
                }).trigger('changed');
        },

        _initConditionOperation: function($condition, operation) {
            operation = operation || this.options.operations[0] || '';
            $('<div class="operator"/>')
                .attr('data-value', '')
                .data('value', operation)
                .prependTo($condition)
                .dropdownSelect({
                    buttonClass: 'btn btn-mini',
                    options: $condition.is('[condition-type=aggregated-condition-item]') ?
                        [operation] : this.options.operations,
                    selected: operation
                });
        },

        _onChangeOperator: function(e) {
            $(e.target)
                .data('value', e.value)
                .trigger('changed');
        },

        _onConditionClose: function(e) {
            var $condition = $(e.target).parent();
            var $group = $condition.parent();
            e.preventDefault();
            $condition.remove();
            this._updateOperators();
            $group.trigger('changed');
        },

        _onChanged: function() {
            this._setSourceValue(this.$rootCondition.data('value'));
            this._updateRootAggregatedCondition(
                this.$rootCondition.find('>[data-criteria]').has('[data-criteria=aggregated-condition-item]')
            );
        },

        _setSourceValue: function(value) {
            if (this.options.sourceValueSelector) {
                $(this.options.sourceValueSelector).val(JSON.stringify(value));
            }
        },

        _getSourceValue: function() {
            var value;
            if (this.options.sourceValueSelector) {
                value = $(this.options.sourceValueSelector).val();
            }
            return value ? JSON.parse(value) : [];
        }
    });

    return $;
});
