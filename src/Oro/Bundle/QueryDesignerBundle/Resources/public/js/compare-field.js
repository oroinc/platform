/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'oro/translator', 'oro/query-designer/util', 'jquery-ui', 'jquery.select2'], function ($, _, __, util) {
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

            this.options.fields = [{"name":"name","type":"string","label":"Account name"},{"name":"createdAt","type":"datetime","label":"Created"},{"name":"field_extend_description","type":"text","label":"Description"},{"name":"field_extend_email","type":"string","label":"Email"},{"name":"field_extend_employees","type":"integer","label":"Employees"},{"name":"field_extend_fax","type":"string","label":"Fax"},{"name":"id","type":"integer","label":"Id","identifier":true},{"name":"field_extend_ownership","type":"string","label":"Ownership"},{"name":"field_extend_phone","type":"string","label":"Phone"},{"name":"field_extend_rating","type":"string","label":"Rating"},{"name":"field_extend_ticker_symbol","type":"string","label":"Ticker Symbol"},{"name":"updatedAt","type":"datetime","label":"Updated"},{"name":"field_extend_website","type":"string","label":"Website"},{"name":"billingAddress","label":"Billing Address","relation_type":"ref-one","related_entity_name":"Oro\\Bundle\\AddressBundle\\Entity\\Address","related_entity_label":"Address","related_entity_plural_label":"Addresses","related_entity_fields":[{"name":"city","type":"string","label":"City"},{"name":"created","type":"datetime","label":"Created at"},{"name":"firstName","type":"string","label":"First name"},{"name":"id","type":"integer","label":"Id","identifier":true},{"name":"label","type":"string","label":"Label"},{"name":"lastName","type":"string","label":"Last name"},{"name":"middleName","type":"string","label":"Middle name"},{"name":"namePrefix","type":"string","label":"Name prefix"},{"name":"nameSuffix","type":"string","label":"Name suffix"},{"name":"organization","type":"string","label":"Organization"},{"name":"regionText","type":"string","label":"State"},{"name":"street","type":"string","label":"Street"},{"name":"street2","type":"string","label":"Street 2"},{"name":"updated","type":"datetime","label":"Updated at"},{"name":"postalCode","type":"string","label":"Zip\/postal code"}]},{"name":"contacts","label":"Contacts","relation_type":"ref-many","related_entity_name":"OroCRM\\Bundle\\ContactBundle\\Entity\\Contact","related_entity_label":"Contact","related_entity_plural_label":"Contacts","related_entity_fields":[{"name":"birthday","type":"datetime","label":"Birthday"},{"name":"createdAt","type":"datetime","label":"Created At"},{"name":"description","type":"text","label":"Description"},{"name":"email","type":"string","label":"Email"},{"name":"facebook","type":"string","label":"Facebook"},{"name":"fax","type":"string","label":"Fax"},{"name":"firstName","type":"string","label":"First name"},{"name":"gender","type":"string","label":"Gender"},{"name":"googlePlus","type":"string","label":"Google+"},{"name":"id","type":"integer","label":"Id","identifier":true},{"name":"jobTitle","type":"string","label":"Job Title"},{"name":"lastName","type":"string","label":"Last name"},{"name":"linkedIn","type":"string","label":"LinkedIn"},{"name":"middleName","type":"string","label":"Middle name"},{"name":"namePrefix","type":"string","label":"Name prefix"},{"name":"nameSuffix","type":"string","label":"Name suffix"},{"name":"skype","type":"string","label":"Skype"},{"name":"twitter","type":"string","label":"Twitter"},{"name":"updatedAt","type":"datetime","label":"Updated At"}]},{"name":"defaultContact","label":"Default contact","relation_type":"ref-one","related_entity_name":"OroCRM\\Bundle\\ContactBundle\\Entity\\Contact","related_entity_label":"Contact","related_entity_plural_label":"Contacts","related_entity_fields":[{"name":"birthday","type":"datetime","label":"Birthday"},{"name":"createdAt","type":"datetime","label":"Created At"},{"name":"description","type":"text","label":"Description"},{"name":"email","type":"string","label":"Email"},{"name":"facebook","type":"string","label":"Facebook"},{"name":"fax","type":"string","label":"Fax"},{"name":"firstName","type":"string","label":"First name"},{"name":"gender","type":"string","label":"Gender"},{"name":"googlePlus","type":"string","label":"Google+"},{"name":"id","type":"integer","label":"Id","identifier":true},{"name":"jobTitle","type":"string","label":"Job Title"},{"name":"lastName","type":"string","label":"Last name"},{"name":"linkedIn","type":"string","label":"LinkedIn"},{"name":"middleName","type":"string","label":"Middle name"},{"name":"namePrefix","type":"string","label":"Name prefix"},{"name":"nameSuffix","type":"string","label":"Name suffix"},{"name":"skype","type":"string","label":"Skype"},{"name":"twitter","type":"string","label":"Twitter"},{"name":"updatedAt","type":"datetime","label":"Updated At"}]},{"name":"owner","label":"Owner","relation_type":"ref-one","related_entity_name":"Oro\\Bundle\\UserBundle\\Entity\\User","related_entity_label":"User","related_entity_plural_label":"Users","related_entity_icon":"icon-user","related_entity_fields":[{"name":"image","type":"string","label":"Avatar"},{"name":"birthday","type":"date","label":"Birthday"},{"name":"confirmationToken","type":"string","label":"Confirmation token"},{"name":"createdAt","type":"datetime","label":"Created"},{"name":"email","type":"string","label":"Email"},{"name":"firstName","type":"string","label":"First name"},{"name":"id","type":"integer","label":"Id","identifier":true},{"name":"lastLogin","type":"datetime","label":"Last logged in"},{"name":"lastName","type":"string","label":"Last name"},{"name":"loginCount","type":"integer","label":"Login count"},{"name":"middleName","type":"string","label":"Middle name"},{"name":"namePrefix","type":"string","label":"Name prefix"},{"name":"nameSuffix","type":"string","label":"Name suffix"},{"name":"password","type":"string","label":"Password"},{"name":"passwordRequestedAt","type":"datetime","label":"Password requested at"},{"name":"salt","type":"string","label":"Salt"},{"name":"enabled","type":"boolean","label":"Status"},{"name":"updatedAt","type":"datetime","label":"Updated"},{"name":"username","type":"string","label":"Username"}]},{"name":"shippingAddress","label":"Shipping Address","relation_type":"ref-one","related_entity_name":"Oro\\Bundle\\AddressBundle\\Entity\\Address","related_entity_label":"Address","related_entity_plural_label":"Addresses","related_entity_fields":[{"name":"city","type":"string","label":"City"},{"name":"created","type":"datetime","label":"Created at"},{"name":"firstName","type":"string","label":"First name"},{"name":"id","type":"integer","label":"Id","identifier":true},{"name":"label","type":"string","label":"Label"},{"name":"lastName","type":"string","label":"Last name"},{"name":"middleName","type":"string","label":"Middle name"},{"name":"namePrefix","type":"string","label":"Name prefix"},{"name":"nameSuffix","type":"string","label":"Name suffix"},{"name":"organization","type":"string","label":"Organization"},{"name":"regionText","type":"string","label":"State"},{"name":"street","type":"string","label":"Street"},{"name":"street2","type":"string","label":"Street 2"},{"name":"updated","type":"datetime","label":"Updated at"},{"name":"postalCode","type":"string","label":"Zip\/postal code"}]}];
            self.element.append(self.template(this.options));

            var $select = self.element.find('select');
            $select.select2({collapsibleResults: true});
            $select.change(function () {
                var $option = $select.find(':selected');
                var conditions = self._getFieldApplicableConditions($option);
                var filterIndex = self._getActiveFilterName(conditions);
                self._render(filterIndex);
            });

            if (this.options.data) {
                var data = this.options.data;
                this.element.data('value', data);
                $select.val(data.columnName).change();
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
            var self = this;

            var metadata = self._getFiltersMetadata();
            var filterOptions = metadata.filters[filterIndex];
            var filterModuleName = getFilterModuleName(filterOptions.type);

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

            this.element.find('.active-filter').empty().append(this.filter.$el);

            this.filter.on('update', this._onUpdate.bind(this));

            this._deserialize();
        },

        _serialize: function () {
            var value = {
                columnName: this.element.find('select').val(),
                criterion: {
                    filter: this.filter.type,
                    data: this.filter.getValue()
                }
            };
            this.element.data('value', value);
        },

        _deserialize: function () {
            var value = this.element.data('value');
            if (value) {
                this.filter.setValue(value.criterion.data);
            }
        },

        _triggerSerialize: function () {
            this.element.trigger('serialize');
        },

        _onUpdate: function () {
            this._serialize();
            this._triggerSerialize();
        }
    });
});
