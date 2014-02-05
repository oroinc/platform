/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'oro/translator', 'orofilter/js/map-filter-module-name', 'oro/query-designer/util',
    'oro/entity-field-select-util', 'oro/entity-field-view', 'jquery-ui', 'jquery.select2'
    ], function ($, _, __, mapFilterModuleName, util, EntityFieldUtil, EntityFieldView) {
    'use strict';

    $.widget('oroquerydesigner.fieldCondition', {
        options: {
            util: {}
        },

        _create: function () {
            var entityFieldUtil = new EntityFieldUtil(this.$select),
                select2Options = this.options.select2;
            $.extend(entityFieldUtil, this.options.util);

            this.options.fields = entityFieldUtil._convertData(this.options.fields, this.options.entity, null);

            if (select2Options.formatSelectionTemplate) {
                (function () {
                    var template = _.template(select2Options.formatSelectionTemplate);
                    select2Options.formatSelection = function (item) {
                        return item.id ? template(entityFieldUtil.splitFieldId(item.id)) : '';
                    };
                }());
            }

            this.$select
                .data('entity', this.options.entity)
                .data('data', this.options.fields);

            this._getFieldApplicableConditions = function (fieldId) {
                return EntityFieldView.prototype.getFieldApplicableConditions.call(entityFieldUtil, fieldId);
            };
        }
    });

    /**
     * Compare field widget
     */
    $.widget('oroquerydesigner.fieldCondition', $.oroquerydesigner.fieldCondition, {
        options: {
            fields: [],
            filterMetadataSelector: '',
            select2: {
                collapsibleResults: true,
                dropdownAutoWidth: true
            }
        },

        _create: function () {
            var data = this.element.data('value');

            this.template = _.template('<input class="select" /><span class="active-filter" />');
            this.element.append(this.template(this.options));
            this.$select = this.element.find('input.select');

            this._super();

            this.$select.select2($.extend({
                data: this.options.fields
            }, this.options.select2));

            if (data && data.columnName) {
                this.$select.select2('val', data.columnName, true);
                this._renderFilter(data.columnName);
            }

            this.$select.change(_.bind(function (e) {
                $(':focus').blur();
                if (e.added) {
                    // reset current value on field change
                    this.element.data('value', {});
                    this._renderFilter(e.added.id);
                }
            }, this));
        },

        _renderFilter: function (fieldId) {
            var self = this,
                conditions = self._getFieldApplicableConditions(fieldId),
                filterIndex = self._getActiveFilterName(conditions);
            self._createFilter(filterIndex, function () {
                self._appendFilter();
                self._onUpdate();
            });
        },

        _getFiltersMetadata: function () {
            var metadata = $(this.options.filterMetadataSelector).data('metadata');

            metadata.filters.push({
                type: 'none',
                applicable: {},
                popupHint: __('Choose a column first')
            });

            return _.extend({ filters: [] }, metadata);
        },

        _getActiveFilterName: function (criteria) {
            var foundFilterName = null;

            if (!_.isUndefined(criteria.filter)) {
                // the criteria parameter represents a filter
                foundFilterName = criteria.filter;
                return foundFilterName;
            }

            var foundFilterMatchedBy = null;

            _.each(this._getFiltersMetadata().filters, function (filter, filterName) {
                var isApplicable = false;

                if (!_.isEmpty(filter.applicable)) {
                    // check if a filter conforms the given criteria
                    var matched = util.matchApplicable(filter.applicable, criteria);

                    if (!_.isUndefined(matched)) {
                        if (_.isNull(foundFilterMatchedBy)
                            // new rule is more exact
                            || _.size(foundFilterMatchedBy) < _.size(matched)
                            // 'type' rule is most low level one, so any other rule can override it
                            || (_.size(foundFilterMatchedBy) == 1 && _.has(foundFilterMatchedBy, 'type'))) {
                            foundFilterMatchedBy = matched;
                            isApplicable = true;
                        }
                    }
                } else if (_.isNull(foundFilterName)) {
                    // if a filter was nor found so far, use a default filter
                    isApplicable = true;
                }
                if (isApplicable) {
                    foundFilterName = filterName;
                }
            });

            return foundFilterName;
        },

        _createFilter: function (filterIndex, cb) {
            var self = this;

            var metadata = self._getFiltersMetadata();
            var filterOptions = metadata.filters[filterIndex];
            var filterModuleName = mapFilterModuleName(filterOptions.type);

            requirejs([filterModuleName], function (Filter) {
                self.filterIndex = filterIndex;
                var filter = self.filter = new (Filter.extend(filterOptions));
                cb(filter);
            });
        },

        _appendFilter: function () {
            var value = this.element.data('value');
            if (value && value.criterion) {
                this.filter.value = value.criterion.data;
            }
            this.filter.render();

            var $filter = this.element.find('.active-filter').empty().append(this.filter.$el);

            $filter.on('change', _.bind(this.filter.apply, this.filter));
            this.filter.on('update', _.bind(this._onUpdate, this));
        },

        _onUpdate: function () {
            var value;

            if (!this.filter.isEmptyValue()) {
                value = {
                    columnName: this.element.find('input.select').select2('val'),
                    criterion: {
                        filter: this.filter.type,
                        data: this.filter.getValue()
                    }
                };
            } else {
                value = {};
            }

            this.element.data('value', value);
            this.element.trigger('changed');
        }
    });
});
