/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'jquery-ui', 'oroui/js/dropdown-select', './compare-field'], function ($, _) {
    'use strict';

    /**
     * Conditions group widget
     */
    $.widget('oro.conditionsGroup', {
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
            conditionsGroupSelector: '#segmentation-conditions',
            criteriaList: {
                helper: 'clone'
            },
            criteriaListSelector: '#filter-criteria-list',
            helperClass: 'ui-grabbing',
            conditionHTML: '<li class="condition" />',
            compareFieldsHTML: '<div class="field-filter" />',
            conditionsGroupHTML: '<ul class="conditions-group" />'
        },

        _create: function () {
            this._prepareOptions();
            this._initCriteriaList(this.options.criteriaListSelector);
            this._initConditionsGroup(this.options.conditionsGroupSelector);
            this._updateOperators();
            $(this.options.conditionsGroupSelector)
                .on('closed', _.debounce(_.bind(this._updateOperators, this), 1))
                .on('change', '.operator', _.bind(this._onChangeOperator, this));
        },

        _prepareOptions: function () {
            var opts = this.options;
            opts.conditionsGroup = _.extend({}, opts.sortable, opts.conditionsGroup);
            opts.conditionsGroup.appendTo = opts.criteriaListSelector;
            opts.conditionsGroup.helper = _.bind(this._createHelper, this);
            opts.conditionsGroup.update = _.bind(this._onUpdate, this);
            opts.criteriaList = _.extend({}, opts.sortable, opts.criteriaList);
            opts.criteriaList.start = _.bind(this._onCriteriaGrab, this);
            opts.criteriaList.stop = _.bind(this._onCriteriaDrop, this);
        },

        _initCriteriaList: function (el) {
            $(el).sortable(this.options.criteriaList);
        },

        _initConditionsGroup: function (el) {
            var $el = $(el),
                sortableConnectWith = this.options.sortable.connectWith;
            if (!$el.is(sortableConnectWith)) {
                $el = $el.find(sortableConnectWith);
                if (!$el.length) {
                    $el = $(this.options.conditionsGroupHTML).appendTo($el.end());
                }
            }
            $el.sortable(this.options.conditionsGroup);
        },

        _onCriteriaGrab: function (e, ui) {
            // create clone element just to remember place of item
            ui.item.data('clone', ui.item.clone().insertAfter(ui.item)).removeAttr('style');
            ui.helper.addClass(this.options.helperClass);
        },

        _onCriteriaDrop: function (e, ui) {
            // put item back instead of it's clone
            ui.item.data('clone').replaceWith(ui.item.removeData('clone'));
        },

        _createCondition: function (criteria, options) {
            var $el;
            switch (criteria) {
            case 'compare-fields':
                $el = $(this.options.compareFieldsHTML);
                $el.compareField(options);
                break;
            case 'conditions-group':
                $el = $(this.options.conditionsGroupHTML);
                this._initConditionsGroup($el);
                break;
            }
            $el.wrap(this.options.conditionHTML);
            return $el.parent().attr('data-criteria', criteria).attr('data-value', Math.random());
        },

        _createHelper: function (e, $el) {
            var $criteria = $(this.options.conditionsGroup.appendTo)
                .find('[data-criteria="' + $el.data('criteria') + '"]');
            return $criteria.clone()
                .css({width: $criteria.outerWidth(), height: $criteria.outerHeight()})
                .addClass(this.options.helperClass);
        },

        _onUpdate: function (e, ui) {
            var $condition, options;
            // new condition
            if (ui.sender && ui.sender.is(this.options.criteriaListSelector)) {
                options = _.omit(ui.item.data(), ['clone', 'sortableItem', 'criteria']);
                $condition = this._createCondition(ui.item.data('criteria'), options);
                $condition.insertBefore(ui.item);
                $condition.prepend('<a class="close" data-dismiss="alert" href="#">&times;</a>');
            } else {
                $condition = ui.item;
            }
            this._updateOperators();
        },

        _updateOperators: function () {
            var $conditions = $(this.options.conditionsGroupSelector)
                .find(this.options.conditionsGroup.connectWith + this.options.conditionsGroup.items);
            // remove operators for first items in groups
            $conditions.filter(':first-child')
                .find('>.operator').remove();
            // add operators for needed conditions
            $conditions.filter(':not(:first-child)').not(':has(>.operator)')
                .each(function () {
                    var $condition = $(this),
                        operation = $condition.data('operation') || 'and';
                    $condition.data('operation', operation);
                    $('<div class="operator"/>').prependTo($condition).dropdownSelect({
                        buttonClass: 'btn btn-mini',
                        options: ['and', 'or'],
                        selected: operation
                    });
                });

        },

        _onChangeOperator: function (e) {
            $(e.target).data('operation', e.value);
        }
    });
});
