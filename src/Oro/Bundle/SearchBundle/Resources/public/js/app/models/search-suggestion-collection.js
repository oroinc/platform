define(function(require) {
    'use strict';

    const _ = require('underscore');
    const LoadMoreCollection = require('oroui/js/app/models/load-more-collection');

    const SearchSuggestionCollection = LoadMoreCollection.extend({
        minSearchLength: 0,

        limitPropertyName: 'max_results',

        /**
         * @inheritdoc
         */
        constructor: function SearchSuggestionCollection(...args) {
            SearchSuggestionCollection.__super__.constructor.apply(this, args);
        },

        initialize: function(models, options) {
            _.extend(this, _.pick(options, 'minSearchLength'));

            SearchSuggestionCollection.__super__.initialize.call(this, models, options);
        },

        parse: function(response) {
            this._state.set('totalItemsQuantity', response.records_count);
            return response.data;
        },

        setSearchParams: function(search, from) {
            if (search.length < this.minSearchLength) {
                this._route.set({
                    search: search,
                    from: from
                }, {
                    silent: true
                });

                this.unsync();
                this.reset();
                this._lastUrl = null;
            } else {
                if (search !== this._route.get('search')) {
                    this.unsync();
                    this.reset();
                }

                this._route.set({
                    search: search,
                    from: from
                });
            }
        }
    });

    return SearchSuggestionCollection;
});
