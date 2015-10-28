define(function(require) {
    'use strict';

    var AutocompleteComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var BaseComponent = require('oroui/js/app/components/base/component');

    AutocompleteComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            route_name: '',
            route_parameters: {},
            timeout: 500
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {String}
         */
        url: '',

        /**
         * @property {Object}
         */
        resultsMapping: {},

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            AutocompleteComponent.__super__.initialize.apply(this, arguments);

            var thisOptions = {
                config: {
                    source: _.bind(this.source, this),
                    matcher: _.bind(this.matcher, this),
                    sorter: _.bind(this.sorter, this)
                }
            };
            this.options = $.extend(true, thisOptions, this.options, options || {});
            this.$el = options._sourceElement;

            if (this.options.route_name) {
                this.url = routing.generate(
                    this.options.route_name,
                    this.options.route_parameters
                );
            }

            if (!_.isFunction(this.options.result_template)) {
                this.options.result_template = _.template(this.options.result_template);
            }

            this.$el.typeahead(this.options.config);
        },

        /**
         * @param {String} query
         * @param {Function} callback
         */
        source: function(query, callback) {
            var self = this;

            if (this.source.timeoutId) {
                clearTimeout(this.source.timeoutId);
            }

            this.source.timeoutId = setTimeout(function() {
                self.source.timeoutId = null;

                $.getJSON(self.url, {
                    query: query
                }, function(response) {
                    callback(self.prepareResults(response));
                });
            }, this.options.timeout);
        },

        /**
         * @param {String} item
         * @returns {Boolean}
         */
        matcher: function(item) {
            return true;//matched on server
        },

        /**
         * @param {Array} items
         * @returns {Array}
         */
        sorter: function(items) {
            return items;//sorted on server
        },

        /**
         * @param {Object} response
         * @returns {Array}
         */
        prepareResults: function(response) {
            var self = this;
            this.resultsMapping = {};
            return _.map(response.results || [], function(item) {
                var result = $.trim(self.renderResult(item));
                self.resultsMapping[result] = item;
                return result;
            });
        },

        /**
         * @param {Object} result
         * @returns {String}
         */
        renderResult: function(result) {
            return this.options.result_template(result);
        }
    });

    return AutocompleteComponent;
});
