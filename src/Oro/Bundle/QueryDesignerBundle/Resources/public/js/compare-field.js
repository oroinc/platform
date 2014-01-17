/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'oro/translator', 'orofilter/js/map-filter-module-name', 'oro/query-designer/util',
    'jquery-ui', 'jquery.select2'], function ($, _, __, mapFilterModuleName, util) {
    'use strict';

    /**
     * Compare field widget
     */
    $.widget('oro.compareField', {
        options: {
            fields: [],
            filterMetadataSelector: '#report-designer'
        },

        _create: function() {
            var self = this;

            self.template = _.template('<div class="compare-field"><input class="select compare-field" /><div class="active-filter" /></div>');
            self.element.append(self.template(this.options));

            var $select = self.element.find('input.select');
            $select.select2({
                collapsibleResults: false,
                data: this.options.fields
            });
            $select.change(function (e) {
                if (e.added) {
                    var conditions = self._getFieldApplicableConditions(e.added);
                    var filterIndex = self._getActiveFilterName(conditions);
                    self._render(filterIndex);
                }
            });

            var data = this.element.data('value');
            if (data && data.columnName) {
                $select.select2('val', data.columnName, true);
            }
        },

        _render: function (filterIndex) {
            var self = this;
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

        _getFieldApplicableConditions: function (item) {
            var result = {
                parent_entity: null,
                entity: this.options.entityName,
                field: item.value
            };

            var chain = result.field.split(',');

            if (_.size(chain) > 1) {
                var field = _.last(chain).split('::');
                result.parent_entity = result.entity;
                result.entity = _.first(field);
                result.field = _.last(field);
                if (_.size(chain) > 2) {
                    var parentField = chain[_.size(chain) - 2].split('::');
                    result.parent_entity = _.first(parentField);
                }
            }

            _.extend(result, _.pick(item, ['type', 'identifier']));

            return result;
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

                if (filter.templateSelector === '#text-filter-template') {
                    filter.templateSelector = '#text-filter-embedded-template';
                    filter.template = _.template($(filter.templateSelector).text());
                }

                if (filter.templateSelector === '#choice-filter-template') {
                    filter.templateSelector = '#choice-filter-embedded-template';
                    filter.template = _.template($(filter.templateSelector).text());
                }

                if (filter.templateSelector === '#date-filter-template') {
                    filter.templateSelector = '#date-filter-embedded-template';
                    filter.template = _.template($(filter.templateSelector).text());
                }

                if (filter.templateSelector === '#select-filter-template') {
                    filter.templateSelector = '#select-filter-embedded-template';
                    filter.template = _.template($(filter.templateSelector).text());
                }

                cb(filter);
            });
        },

        _appendFilter: function () {
            this.filter.render();

            var $filter = this.element.find('.active-filter').empty().append(this.filter.$el);

            var apply = this.filter.apply.bind(this.filter);
            $filter.on('change', apply);
            $filter.find('.choice_value').on('click', apply);
            this.filter.on('update', this._onUpdate.bind(this));

            var value = this.element.data('value');
            if (value) {
                this.filter.setValue(value.criterion.data);
            }
        },

        _onUpdate: function () {
            var value = {
                columnName: this.element.find('input.select').select2('val'),
                criterion: {
                    filter: this.filter.type,
                    data: this.filter.getValue()
                }
            };
            this.element.data('value', value);

            this.element.trigger('changed');
        }
    });
});
