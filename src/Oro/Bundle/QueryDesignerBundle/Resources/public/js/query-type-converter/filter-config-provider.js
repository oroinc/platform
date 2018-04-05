define(function(require) {
    'use strict';

    var FilterConfigProvider;
    var tools = require('oroui/js/tools');
    var _ = require('underscore');

    /**
     * @param {Object} options
     * @constructor
     * @throws TypeError if filtersOptions is missing
     */
    FilterConfigProvider = function FilterConfigProvider(options) {
        if (!options) {
            throw new TypeError('`filtersOptions` is required');
        }

        this.filters = options.filters;
        this.hierarchy = options.hierarchy;
        this.filterModules = {};
    };

    FilterConfigProvider.prototype = {
        constructor: FilterConfigProvider,

        /**
         * Collect filters modules
         *
         * @returns {Promise}
         * @protected
         */
        loadInitModules: function() {
            var modules = [];

            _.each(this.filters, function(filterConfig) {
                if (filterConfig.init_module) {
                    modules.push(filterConfig.init_module);
                }
            }, this);

            return tools.loadModules(_.object(modules, modules), function(modules) {
                this.filterModules = modules;
            }, this);
        },

        /**
         * @param {Object} fieldSignature
         * @returns {Object}
         * @throws TypeError if fieldSignature is missing
         */
        getApplicableFilterConfig: function(fieldSignature) {
            if (!fieldSignature) {
                throw new TypeError('`fieldSignature` is required');
            }

            // the criteria parameter represents a filter
            if (_.has(fieldSignature, 'filter')) {
                return fieldSignature.filter;
            }

            var matchApplicable = function(applicable, fieldSignature) {
                var hierarchy = this.hierarchy[fieldSignature.entity];
                return _.find(applicable, function(item) {
                    return _.every(item, function(value, key) {
                        if (key === 'entity' && hierarchy.length) {
                            return _.indexOf(hierarchy, fieldSignature[key]);
                        }
                        return fieldSignature[key] === value;
                    });
                });
            }.bind(this);

            var matchedBy = {};
            var filterId = null;

            _.each(this.filters, function(filterConfig, id) {
                if (!_.isEmpty(filterConfig.applicable)) {
                    // check if a filter conforms the given criteria
                    var matched = matchApplicable(filterConfig.applicable, fieldSignature);

                    if (matched && (
                        // new rule is more exact
                        _.size(matchedBy) < _.size(matched) ||
                        // 'type' rule is most low level one, so any other rule can override it
                        // example: {type: 'enum'} to be override {entity: 'Oro\Bundle\SalesBundle\Entity\Opportunity'}
                        _.size(matchedBy) === 1 && _.has(matchedBy, 'type')
                    )) {
                        matchedBy = matched;
                        filterId = id;
                    }
                }
            }, this);

            if (!_.isNumber(filterId) ||
                fieldSignature.entity === 'Oro\\Bundle\\AccountBundle\\Entity\\Account' &&
                fieldSignature.field === 'lifetimeValue'
            ) {
                return null;
            }

            var filterConfig = tools.deepClone(this.filters[filterId]);
            var optionsResolver = this.filterModules[filterConfig.init_module];

            if (_.isFunction(optionsResolver)) {
                optionsResolver(filterConfig, fieldSignature);
            }

            return filterConfig;
        },

        /**
         * @param {String} filterType
         * @returns {array}
         */
        getFilterConfigsByType: function(filterType) {
            return _.where(this.filters, {type: filterType});
        },

        /**
         * @param {string} filterName
         * @returns {Object}
         */
        getFilterConfigByName: function(filterName) {
            return _.findWhere(this.filters, {name: filterName});
        }
    };

    /**
     * @export oroquerydesigner/js/query-type-converter/filter-config-provider
     */
    return FilterConfigProvider;
});
