define(function(require) {
    'use strict';

    const tools = require('oroui/js/tools');
    const loadModules = require('oroui/js/app/services/load-modules');
    const _ = require('underscore');

    class FilterConfigProvider {
        /**
         * @param {Object} options
         * @constructor
         * @throws TypeError if filtersOptions is missing
         */
        constructor(options) {
            if (!options) {
                throw new TypeError('`filtersOptions` is required');
            }

            this.filters = options.filters;
            this.hierarchy = options.hierarchy;
            this.filterModules = {};
        }

        /**
         * Collect filters modules
         *
         * @returns {Promise}
         * @protected
         */
        loadInitModules() {
            const modules = [];

            this.filters.forEach(filterConfig => {
                if (filterConfig.init_module) {
                    modules.push(filterConfig.init_module);
                }
            });

            return loadModules(Object.fromEntries(modules.map(name => [name, name])))
                .then(modules => {
                    this.filterModules = modules;
                });
        }

        /**
         * @param {Object} fieldSignature
         * @returns {Object}
         * @throws TypeError if fieldSignature is missing
         */
        getApplicableFilterConfig(fieldSignature) {
            if (!fieldSignature) {
                throw new TypeError('`fieldSignature` is required');
            }

            // the criteria parameter represents a filter
            if (_.has(fieldSignature, 'filter')) {
                return fieldSignature.filter;
            }

            const matchApplicable = (applicable, fieldSignature) => {
                const hierarchy = this.hierarchy[fieldSignature.entity];
                return _.find(applicable, item => {
                    return _.every(item, (value, key) => {
                        if (key === 'entity' && hierarchy.length) {
                            return _.indexOf(hierarchy, fieldSignature[key]);
                        }
                        return fieldSignature[key] === value;
                    });
                });
            };

            let matchedBy = {};
            let filterId = null;

            this.filters.forEach((filterConfig, id) => {
                if (!_.isEmpty(filterConfig.applicable)) {
                    // check if a filter conforms the given criteria
                    const matched = matchApplicable(filterConfig.applicable, fieldSignature);

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

            const filterConfig = tools.deepClone(this.filters[filterId]);
            const optionsResolver = this.filterModules[filterConfig.init_module];

            if (_.isFunction(optionsResolver)) {
                optionsResolver(filterConfig, fieldSignature);
            }

            return filterConfig;
        }

        /**
         * @param {String} type
         * @returns {array}
         */
        getFilterConfigsByType(type) {
            return _.where(this.filters, {type});
        }

        /**
         * @param {string} filterName
         * @returns {Object}
         */
        getFilterConfigByName(filterName) {
            return _.findWhere(this.filters, {name: filterName});
        }
    }

    /**
     * @export oroquerydesigner/js/query-type-converter/filter-config-provider
     */
    return FilterConfigProvider;
});
