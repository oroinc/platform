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
            properties: [],
            timeout: 100
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
         * @property {String}
         */
        lastSearch: null,

        /**
         * @property {Object}
         */
        waitingSearch: {},

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            AutocompleteComponent.__super__.initialize.apply(this, arguments);

            var thisOptions = {
                selection_template: _.bind(this.renderSelection, this),
                config: {
                    source: _.bind(this.source, this),
                    matcher: _.bind(this.matcher, this),
                    sorter: _.bind(this.sorter, this),
                    show: this.show
                }
            };
            this.options = $.extend(true, thisOptions, this.options, options || {});
            this.$el = options._sourceElement;
            this.$el.attr('autocomplete', 'off');

            if (this.options.route_name) {
                this.url = routing.generate(
                    this.options.route_name,
                    this.options.route_parameters
                );
            }

            if (!_.isFunction(this.options.selection_template) && !_.isEmpty(this.options.selection_template)) {
                this.options.selection_template = _.template(this.options.selection_template);
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

            if (this.lastSearch === query) {
                this.$el.typeahead('show');
                return;
            }

            if (this.waitingSearch[query]) {
                return;
            }

            this.$el.typeahead('hide');

            this.source.timeoutId = setTimeout(function() {
                self.source.timeoutId = null;
                self.waitingSearch[query] = true;

                $.ajax({
                    url: self.url,
                    data: {query: query},
                    success: function(response) {
                        self.sourceCallback(query, callback, response);
                    },
                    error: function() {
                        self.sourceCallback(query, callback, {});
                    }
                });
            }, this.options.timeout);
        },

        sourceCallback: function(query, callback, response) {
            var results = this.prepareResults(response);
            callback(this.$el.is(':focus') ? results : []);

            this.lastSearch = query;
            delete this.waitingSearch[query];
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

        show: function() {
            this.constructor.prototype.show.apply(this);

            var $window = $(window);
            var viewportBottom = $window.scrollTop() + $window.height();
            var elementHeight = this.$element.outerHeight(false);
            var resultsTop = this.$element.offset().top + elementHeight;
            var resultsHeight = this.$menu.outerHeight(false);
            var enoughBelow = resultsTop + resultsHeight <= viewportBottom;

            if (!enoughBelow) {
                var aboveTop = this.$menu.css('top').replace('px', '') - resultsHeight - elementHeight;
                this.$menu.css('top', aboveTop + 'px');
            }
        },

        /**
         * @param {Object} response
         * @returns {Array}
         */
        prepareResults: function(response) {
            var self = this;
            this.resultsMapping = {};
            return _.map(response.results || [], function(item) {
                var result = $.trim(self.options.selection_template(item));
                self.resultsMapping[result] = item;
                return result;
            });
        },

        /**
         * @param {Object} result
         * @returns {String}
         */
        renderSelection: function(result) {
            var title = '';
            if (result) {
                if (this.options.properties.length === 0) {
                    if (result.text !== undefined) {
                        title = result.text;
                    }
                } else {
                    var values = [];
                    _.each(this.options.properties, function(property) {
                        values.push(result[property]);
                    });
                    title = values.join(' ');
                }
            }
            return title;
        }
    });

    return AutocompleteComponent;
});
