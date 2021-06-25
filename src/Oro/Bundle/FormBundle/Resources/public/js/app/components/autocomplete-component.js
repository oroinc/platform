define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const routing = require('routing');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const AutocompleteComponent = BaseComponent.extend({
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
         * @inheritdoc
         */
        constructor: function AutocompleteComponent(options) {
            AutocompleteComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            AutocompleteComponent.__super__.initialize.call(this, options);

            // add debounce to search method
            this._searchForResults = _.debounce(this._searchForResults.bind(this), this.debounceWait);

            const thisOptions = {
                selection_template: this.renderSelection.bind(this),
                config: {
                    source: this.source.bind(this),
                    matcher: this.matcher.bind(this),
                    updater: this.updater.bind(this),
                    sorter: this.sorter.bind(this),
                    show: this.show,
                    hide: this.hide
                }
            };

            this.options = $.extend(true, thisOptions, this.options, options || {});
            this.$el = options._sourceElement;

            this.$el.attr('autocomplete', 'off');

            const dropClasses = this.$el.data('dropdown-classes');
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
            const $el = this.$el;

            if (this.lastSearch === query) {
                $el.typeahead('show');
                return;
            }

            $el.typeahead('hide');
            this._searchForResults(query, callback);
        },

        _searchForResults: function(query, callback) {
            const self = this;

            if (this.disposed) {
                return;
            }

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
            const results = this.prepareResults(response);
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
            const pos = $.extend({}, this.$element.position(), {
                height: this.$element[0].offsetHeight
            });

            const $autocomplete = this.$holder.length ? this.$holder : this.$menu;
            const direction = {};

            if (_.isRTL()) {
                direction.right = this._calculateRightPosition();
            } else {
                direction.left = pos.left;
            }

            if (this.$holder.length) {
                this.$holder
                    .insertAfter(this.$element)
                    .css({
                        top: pos.top + pos.height,
                        ...direction
                    })
                    .append(this.$menu)
                    .show();
            } else {
                this.$menu
                    .insertAfter(this.$element)
                    .css({
                        top: pos.top + pos.height,
                        ...direction
                    })
                    .show();
            }

            this.shown = true;

            const $window = $(window);
            const viewportBottom = $window.scrollTop() + $window.height();
            const autocompleteHeight = $autocomplete.outerHeight(false);
            const autocompleteTop = $autocomplete.offset().top;
            const enoughBelow = autocompleteTop + autocompleteHeight <= viewportBottom;
            const enoughAbove = this.$element.offset().top > autocompleteHeight;

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
            const self = this;
            this.resultsMapping = {};
            return _.map(response.results || [], function(item) {
                const result = self.options.selection_template(item).trim();
                self.resultsMapping[result] = item;
                return result;
            });
        },

        /**
         * @param {Object} result
         * @returns {String}
         */
        renderSelection: function(result) {
            let title = '';
            if (result) {
                if (this.options.properties.length === 0) {
                    if (result.text !== undefined) {
                        title = result.text;
                    }
                } else {
                    const values = [];
                    _.each(this.options.properties, function(property) {
                        values.push(result[property]);
                    });
                    title = values.join(' ');
                }
            }
            return title;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.jqXHR) {
                this.jqXHR.abort();
            }

            AutocompleteComponent.__super__.dispose.call(this);
        }
    });

    return AutocompleteComponent;
});
