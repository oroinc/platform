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
            filterMetadataSelector: '.report-designer',
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
            this._deserialize();
            this._initCriteriaList(this.options.criteriaListSelector);
            this._initConditionsGroup(this.options.conditionsGroupSelector);
            this._updateOperators();
            $(this.options.conditionsGroupSelector)
                .on('closed', _.debounce(_.bind(this._updateOperators, this), 1))
                .on('change', '.operator', _.bind(this._onChangeOperator, this))
                .on('serialize', _.bind(this._serialize, this));
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
                $.extend(options, {
                    filterMetadataSelector: this.options.filterMetadataSelector
                })
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
            this._serialize();
        },

        _onChangeOperator: function (e) {
            $(e.target).data('operation', e.value);
            this._serialize();
        },

        _serialize: function () {
            var $group = $(this.options.conditionsGroupSelector).children('ul.conditions-group').first();
            var value = [];
            this._serializeConditionsGroup(value, $group);
            this.element.data('value', value)
            console.log('_ser', this.element.data('value'));
        },

        _serializeConditionsGroup: function (result, $group) {
            var self = this;

            $group.children('li.condition').each(function () {
                var $condition = $(this);
                var criteria = $condition.data('criteria');
                var resultItem = {
                    criteria: criteria
                };
                var operation = $condition.children('.operator').first().data('operation');
                if (operation) {
                    resultItem.operation = operation;
                }

                switch (criteria) {
                case 'conditions-group':
                    resultItem.value = [];
                    self._serializeConditionsGroup(resultItem.value, $condition.children('ul.conditions-group').first());
                    break;
                case 'compare-fields':
                    self._serializeCompareFields(resultItem, $condition);
                    break;
                }

                result.push(resultItem);
            });
        },

        _serializeCompareFields: function (resultItem, $condition) {
            resultItem.value = $condition.children('div.field-filter').first().data('value') || {};
        },

        _deserialize: function () {
            var val = this.element.data('value');
            this._deserializeConditionsGroup($(this.options.conditionsGroupSelector).children('ul.conditions-group').first(), val);
        },

        _deserializeConditionsGroup: function ($parent, val) {
            var self = this;

            val.forEach(function (item) {
                switch (item.criteria) {
                case 'conditions-group':
                    var $child = $parent.append('<li class="condition" data-criteria="conditions-group">\
                            <a class="close" data-dismiss="alert" href="#">×</a>\
                            <ul class="conditions-group">\
                            </ul>\
                        </li>').find('.conditions-group');
                    self._deserializeConditionsGroup($child, item.value);
                    break;
                case 'compare-fields':
                    $parent.append('<li class="condition" data-criteria="compare-fields">\
                        <a class="close" data-dismiss="alert" href="#">×</a>\
                        <div class="field-filter">\
                    </li>').find('.field-filter').compareField({
                        filterMetadataSelector: self.options.filterMetadataSelector,
                        data: item.value
                    });
                    break;
                }
                console.log('_desConGro', item);
            });
        },
    });
});
