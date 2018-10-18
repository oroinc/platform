define(function(require) {
    'use strict';

    var SearchSuggestionView;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var SearchSuggestionCollection = require('orosearch/js/app/models/search-suggestion-collection');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var SearchSuggestionItemView = require('orosearch/js/app/views/search-suggestion-item-view');

    SearchSuggestionView = BaseCollectionView.extend({
        animationDuration: 0,

        /**
         * Used to delay request during user type
         */
        latency: 300,

        minSearchLength: 3,

        itemView: SearchSuggestionItemView,

        listSelector: '[data-role="search-suggestion-list"]',

        fallbackSelector: '[data-role="fallback"]',

        loadingSelector: '[data-role="loading"]',

        events: {
            'keyup [name=search]': 'onKeyUp',
            'keydown [name=search]': 'onKeyDown',
            'change [name=search]': 'updateCollectionSearchParams',
            'change [name=from]': 'updateCollectionSearchParams',
            'mousedown [data-role=search-suggestion-list] a': 'onSuggestionMouseDown',
            'click .nav-content': 'stopPropagation',
            'select2-open.dropdown.data-api': 'stopPropagation'
        },

        listen: {
            'page:beforeChange mediator': 'onPageBeforeChange'
        },

        onSuggestionMouseDown: function() {
            var $search = this.$('[name=search]');

            if ($search.is(':focus')) {
                $search.one('blur', function(e) {
                    e.currentTarget.focus();
                });
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function SearchSuggestionView(options) {
            _.extend(this, _.pick(options, 'latency'));
            this.onKeyUp = _.debounce(this.onKeyUp, this.latency);

            SearchSuggestionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var routeParameters = {
                routeName: 'oro_search_suggestion',
                routeQueryParameterNames: ['search', 'from', 'max_results', 'format'],
                max_results: 7,
                search: '',
                from: ''
            };

            this.collection = new SearchSuggestionCollection([], {
                routeParameters: routeParameters,
                minSearchLength: this.minSearchLength
            });

            SearchSuggestionView.__super__.initialize.apply(this, arguments);
        },

        delegateEvents: function(events) {
            SearchSuggestionView.__super__.delegateEvents.call(this, events);

            var listener = this.onListScroll.bind(this);

            this.$('[data-role="search-suggestion-list"]').on('scroll' + this.eventNamespace(), listener);
        },

        undelegateEvents: function(events) {
            SearchSuggestionView.__super__.undelegateEvents.call(this, events);

            if (this.$el) {
                this.$('[data-role="search-suggestion-list"]').off('scroll' + this.eventNamespace());
            }
        },

        onKeyUp: function(e) {
            this.updateCollectionSearchParams();
        },

        onKeyDown: function(e) {
            var selectedModel = this.collection.findWhere({selected: true});

            if (['ArrowDown', 'ArrowUp'].indexOf(e.key) !== -1) {
                var selectedIndex = this.getSelectedIndex();
                var length = this.collection.length;

                if (e.key === 'ArrowDown') {
                    if (selectedIndex + 1 < length) {
                        selectedIndex++;
                    }
                } else {
                    if (selectedIndex >= 0) {
                        selectedIndex--;
                    }
                }

                this.setSelectedIndex(selectedIndex);
                e.preventDefault();
            } else if (e.key === 'Enter') {
                if (selectedModel) {
                    this.$('[name=search]').blur();
                    mediator.execute('redirectTo', {url: selectedModel.get('record_url')});
                } else if (this.$('[name=search]').val().length === 0) {
                    e.preventDefault();
                }
            }
        },

        setSelectedIndex: function(index) {
            var selectedIndex = this.getSelectedIndex();

            if (selectedIndex !== index) {
                var model = selectedIndex >=0 ? this.collection.at(selectedIndex) : null;

                if (model) {
                    model.set('selected', false);
                }

                model = index >=0 ? this.collection.at(index) : null;

                if (model) {
                    model.set('selected', true);
                    this.scrollToSubview(this.subview('itemView:' + model.cid));
                }
            }
        },

        getSelectedIndex: function() {
            var selectedModel = this.collection.findWhere({selected: true});
            return this.collection.indexOf(selectedModel);
        },

        stopPropagation: function(e) {
            e.stopPropagation();
        },

        onListScroll: function(e) {
            if (this.collection.isSyncing() || !this.collection.hasMore()) {
                return;
            }

            var el = e.target;

            // If user reach bottom of list
            if (el.offsetHeight + el.scrollTop >= el.scrollHeight) {
                var selectedIndex = this.getSelectedIndex();
                this.collection.loadMore().then(function() {
                    this.setSelectedIndex(selectedIndex);
                }.bind(this));
                this.$list.scrollTop(this.$list[0].scrollHeight - this.$list.height());
            }
        },

        onPageBeforeChange: function() {
            this.$('[name=search]').val('');
            this.updateCollectionSearchParams();
        },

        scrollToSubview: function(view) {
            var margin = 10;
            var visibleFrameTop = this.$list.scrollTop();
            var visibleFrameHeight = this.$list.height();
            var top = visibleFrameTop + view.$el.position().top;
            var bottom = top + view.$el.outerHeight(true);

            if (top - margin < visibleFrameTop) {
                this.$list.scrollTop(top - margin);
            } else if (bottom + margin > visibleFrameTop + visibleFrameHeight) {
                this.$list.scrollTop(bottom + margin - visibleFrameHeight);
            }
        },

        updateCollectionSearchParams: function() {
            this.collection.setSearchParams(
                this.$('[name=search]').val(),
                this.$('[name=from]').val()
            );
        }
    });

    return SearchSuggestionView;
});
