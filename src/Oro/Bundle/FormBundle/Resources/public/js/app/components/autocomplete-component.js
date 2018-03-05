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
         * @property {integer}
         */
        debounceWait: 500,

        /**
         * @inheritDoc
         */
        constructor: function AutocompleteComponent() {
            AutocompleteComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            AutocompleteComponent.__super__.initialize.apply(this, arguments);

            // add debounce to search method
            this._searchForResults = _.debounce(this._searchForResults.bind(this), this.debounceWait);

            var thisOptions = {
                selection_template: _.bind(this.renderSelection, this),
                config: {
                    source: _.bind(this.source, this),
                    matcher: _.bind(this.matcher, this),
                    updater: _.bind(this.updater, this),
                    sorter: _.bind(this.sorter, this),
                    show: this.show,
                    hide: this.hide
                }
            };

            this.options = $.extend(true, thisOptions, this.options, options || {});
            this.$el = options._sourceElement;

            this.$el.attr('autocomplete', 'off');

            var dropClasses = this.$el.data('dropdown-classes');
            if (dropClasses) {
                this.options.config = _.assign(this.options.config, {
                    holder: '<div class="' + dropClasses.holder + '"></div>',
                    menu: '<ul class="' + dropClasses.menu + '"></ul>',
                    item: '<li class="' + dropClasses.item + '"><a class="' + dropClasses.link + '" href="#"></a></li>'
                });
            }

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
            var $el = this.$el;

            if (this.lastSearch === query) {
                $el.typeahead('show');
                return;
            }

            $el.typeahead('hide');
            this._searchForResults(query, callback);
        },

        _searchForResults: function(query, callback) {
            var self = this;

            if (this.jqXHR) {
                this.jqXHR.abort(); // abort ajax call with out-dated results
            }

            this.jqXHR = $.ajax({
                url: self.url,
                data: {query: query},
                success: function(response) {
                    self.sourceCallback(query, callback, response);
                },
                error: function() {
                    self.sourceCallback(query, callback, {});
                },
                complete: function() {
                    delete self.jqXHR; // clear
                }
            });
        },

        sourceCallback: function(query, callback, response) {
            var results = this.prepareResults(response);
            callback(this.$el.is(':focus') ? results : []);

            this.lastSearch = query;
        },

        /**
         * @param {String} item
         * @returns {Boolean}
         */
        matcher: function(item) {
            return true;// matched on server
        },

        /**
         * @param {String} item
         * @returns {String}
         */
        updater: function(item) {
            return item;
        },

        /**
         * @param {Array} items
         * @returns {Array}
         */
        sorter: function(items) {
            return items;// sorted on server
        },

        show: function() {
            var pos = $.extend({}, this.$element.position(), {
                height: this.$element[0].offsetHeight
            });

            var $autocomplete = this.$holder.length ? this.$holder : this.$menu;

            if (this.$holder.length) {
                this.$holder
                    .insertAfter(this.$element)
                    .css({
                        top: pos.top + pos.height,
                        left: pos.left
                    })
                    .append(this.$menu)
                    .show();
            } else {
                this.$menu
                    .insertAfter(this.$element)
                    .css({
                        top: pos.top + pos.height,
                        left: pos.left
                    })
                    .show();
            }

            this.shown = true;

            var $window = $(window);
            var viewportBottom = $window.scrollTop() + $window.height();
            var autocompleteHeight = $autocomplete.outerHeight(false);
            var autocompleteTop = $autocomplete.offset().top;
            var enoughBelow = autocompleteTop + autocompleteHeight <= viewportBottom;
            var enoughAbove = this.$element.offset().top > autocompleteHeight;

            if (!enoughBelow && enoughAbove) {
                $autocomplete.css('top', -autocompleteHeight);
            }

            return this;
        },

        hide: function() {
            if (this.$holder.length) {
                this.$holder.hide();
            } else {
                this.$menu.hide();
            }

            this.shown = false;

            return this;
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
