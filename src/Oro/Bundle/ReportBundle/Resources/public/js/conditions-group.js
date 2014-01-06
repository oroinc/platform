/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'backbone', 'jquery-ui', 'ororeport/js/dropdown-select'], function ($, _, Backbone) {
    'use strict';

    /**
     * Basic grid filter
     *
     * @export  ororeport/js/conditions-group
     * @class   oro.report.ConditionsGroup
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        defaultOptions: {
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
                cancel: 'a, input, .btn'
            },
            conditionsGroupSelector: '#segmentation-conditions',
            criteriaList: {},
            criteriaListSelector: '#filter-criteria-list',
            helperClass: 'ui-grabbing',
            conditionHTML: '<li class="condition" />',
            compareFieldsHTML: '<div class="field-filter" />',
            conditionsGroupHTML: '<ul class="conditions-group" />'
        },

        initialize: function (options) {
            this._prepareOptions(options);
            this._initCriteriaList(this.options.criteriaListSelector);
            this._initConditionsGroup(this.options.conditionsGroupSelector);
            this._updateOperators();
            $(this.options.conditionsGroupSelector)
                .on('closed', _.debounce(_.bind(this._updateOperators, this), 1))
                .on('change', '.operator', _.bind(this._onChangeOperator, this));

        },

        _prepareOptions: function (options) {
            this.options = $.extend(true, {}, this.defaultOptions, options);
            this.options.conditionsGroup = _.extend({}, this.options.sortable, this.options.conditionsGroup);
            this.options.conditionsGroup.appendTo = this.options.criteriaListSelector;
            this.options.conditionsGroup.helper = _.bind(this._createHelper, this);
            this.options.conditionsGroup.update = _.bind(this._onUpdate, this);
            this.options.criteriaList = _.extend({}, this.options.sortable, this.options.criteriaList);
            this.options.criteriaList.start = _.bind(this._onCriteriaGrab, this);
            this.options.criteriaList.stop = _.bind(this._onCriteriaDrop, this);
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
            ui.item.clone().removeAttr('style').insertAfter(ui.item);
            ui.helper.addClass(this.options.helperClass);
        },

        _onCriteriaDrop: function (e, ui) {
            ui.item.remove();
        },

        _createCondition: function (criteria) {
            var $el;
            switch (criteria) {
            case 'compare-fields':
                $el = $(this.options.compareFieldsHTML);
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
            var $condition;
            // new condition
            if (ui.sender && ui.sender.is(this.options.criteriaListSelector)) {
                $condition = this._createCondition(ui.item.data('criteria'));
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
