/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'oro/translator', 'oro/query-designer/util', 'jquery-ui'], function ($, _, __, util) {
    'use strict';

    var filterModuleNameTemplate = 'oro/datafilter/{{type}}-filter';
    var filterTypes = {
        string:      'choice',
        choice:      'select',
        selectrow:   'select-row',
        multichoice: 'multiselect',
        boolean:     'select'
    };

    var getFilterModuleName = function (filterTypeName) {
        return filterModuleNameTemplate.replace('{{type}}', filterTypes[filterTypeName] || filterTypeName);
    };

    /**
     * Compare field widget
     */
    $.widget('oro.compareField', {
        _create: function() {
            var self = this;

            self.template = _.template('<select>\
                <option value="" data-label=""></option>\
                <optgroup label="Fields">\
                    <% _.each(fields, function (field) { if (!field.related_entity_fields) {%>\
                    <option value="<%- field.name %>" data-type="<%- field.type %>" \
                        data-label="<%- field.label %>"><%- field.label %></option>\
                    <% } }) %>\
                </optgroup>\
                <% _.each(fields, function (group) { if (group.related_entity_fields) {%>\
                <optgroup label="<%- group.label %>">\
                    <% _.each(group.related_entity_fields, function (field) { %>\
                    <option value="<%- group.name %>,<%- group.related_entity_name %>::<%- field.name %>" \
                        data-type="<%- field.type %>" data-label="<%- field.label %>"><%- field.label %></option>\
                    <% }) %>\
                </optgroup>\
                <% } }) %>\
            </select>\
            <div class="active-filter" />\
            ');

            self.element.append(self.template(this.options));

            var $select = self.element.find('select');
            $select.select2({collapsibleResults: true});
            $select.change(function () {
                var $option = $select.find(':selected');
                self._render($option);
            });

            $select.find('option').first().prop('selected', true).change();
        },

        _render: function ($option) {
            var that = this;

            var conditions = that._getFieldApplicableConditions($option);
            var filterIndex = that._getActiveFilterName(conditions);

            that._createFilter(filterIndex, function (filter) {
                filter.render();

                that.element.find('.active-filter').empty().append(filter.$el);

//                console.log(filter);
            });
        },

        _getFiltersMetadata: function () {
            //var metadata = this.element.closest('[data-metadata]').data('metadata');
            var metadata = $('.report-designer').data('metadata');

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
                field: item.val()
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

            _.extend(result, _.pick(item.data(), ['type', 'identifier']));

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
            var that = this;

            var metadata = that._getFiltersMetadata();
            var filterOptions = metadata.filters[filterIndex];
            var filterModuleName = getFilterModuleName(filterOptions.type);

            requirejs([filterModuleName], function (Filter) {
                var filter = new (Filter.extend(filterOptions));

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

//                console.log(filter.templateSelector);

                cb(filter);
            });
        },
    });
});
