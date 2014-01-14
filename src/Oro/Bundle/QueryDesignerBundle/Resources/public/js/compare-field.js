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
                    <option value="name" data-type="string" data-label="Account name">Account name</option>\
                    <option value="createdAt" data-type="datetime" data-label="Created">Created</option>\
                    <option value="extend_description" data-type="text" data-label="Description">Description</option>\
                    <option value="extend_email" data-type="string" data-label="Email">Email</option>\
                    <option value="extend_employees" data-type="integer" data-label="Employees">Employees</option>\
                    <option value="extend_fax" data-type="string" data-label="Fax">Fax</option>\
                    <option value="id" data-type="integer" data-label="Id" data-identifier="true">Id</option>\
                    <option value="extend_ownership" data-type="string" data-label="Ownership">Ownership</option>\
                    <option value="extend_phone" data-type="string" data-label="Phone">Phone</option>\
                    <option value="extend_rating" data-type="string" data-label="Rating">Rating</option>\
                    <option value="extend_ticker_symbol" data-type="string" data-label="Ticker Symbol">Ticker Symbol</option>\
                    <option value="updatedAt" data-type="datetime" data-label="Updated">Updated</option>\
                    <option value="extend_website" data-type="string" data-label="Website">Website</option>\
                </optgroup>\
                <optgroup label="Billing Address">\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::city" data-type="string" data-label="City">City</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::created" data-type="datetime" data-label="Created at">Created at</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::firstName" data-type="string" data-label="First name">First name</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::id" data-type="integer" data-label="Id" data-identifier="true">Id</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::label" data-type="string" data-label="Label">Label</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::lastName" data-type="string" data-label="Last name">Last name</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::middleName" data-type="string" data-label="Middle name">Middle name</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::namePrefix" data-type="string" data-label="Name prefix">Name prefix</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::nameSuffix" data-type="string" data-label="Name suffix">Name suffix</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::organization" data-type="string" data-label="Organization">Organization</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::regionText" data-type="string" data-label="State">State</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::street" data-type="string" data-label="Street">Street</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::street2" data-type="string" data-label="Street 2">Street 2</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::updated" data-type="datetime" data-label="Updated at">Updated at</option>\
                    <option value="billingAddress,Oro\Bundle\AddressBundle\Entity\Address::postalCode" data-type="string" data-label="Zip/postal code">Zip/postal code</option>\
                </optgroup>\
            </select>\
            <div class="active-filter" />\
            ');

            self.element.append(self.template());

            var $select = self.element.find('select');
            $select.change(function () {
                var $option = $select.find(':selected');
                self._render($option);
            });

            $select.find('option').first().prop('selected', true).change();
        },

        _render: function ($option) {
            var self = this;

            var conditions = self._getFieldApplicableConditions($option);
            var filterIndex = self._getActiveFilterName(conditions);

            self._createFilter(filterIndex, function () {
                self._appendFilter();
                self._serialize();
                self._triggerSerialize();
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
            var self = this;

            var metadata = self._getFiltersMetadata();
            var filterOptions = metadata.filters[filterIndex];
            var filterModuleName = getFilterModuleName(filterOptions.type);

            requirejs([filterModuleName], function (Filter) {
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

                cb();
            });
        },

        _appendFilter: function () {
            this.filter.render();

            this.element.find('.active-filter').empty().append(this.filter.$el);

            this.filter.on('update', this._triggerSerialize.bind(this));
        },

        _serialize: function () {
            var value = {
                type: this.filter.type,
                value: this.filter.getValue()
            };
            this.element.data('value', value);
        },

        _triggerSerialize: function () {
            this.element.trigger('serialize');
        },
    });
});
