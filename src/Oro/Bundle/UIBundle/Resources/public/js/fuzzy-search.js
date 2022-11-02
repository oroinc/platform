define(function(require, exports, module) {
    'use strict';

    const Fuse = require('Fuse');
    const _ = require('underscore');

    const config = _.extend({
        checkScore: 0.49,
        engineOptions: {
            includeScore: true
        }
    }, require('module-config').default(module.id) || {});

    const FuzzySearch = {
        engineOptions: config.engineOptions,

        checkScore: config.checkScore,

        _cache: {},

        clearCache: function() {
            this._cache = {};
        },

        isMatched: function(str, query) {
            return this.getMatches(str, query).length > 0;
        },

        getMatches: function(str, query) {
            let cache = this._cache[str];
            if (!cache) {
                cache = this._cache[str] = {
                    searchEngine: this._newSearchEngine(str),
                    queries: {}
                };
            }

            if (cache.queries[query] === undefined) {
                const matches = cache.searchEngine.search(query);
                cache.queries[query] = this._filterMatches(matches);
            }

            return cache.queries[query];
        },

        _filterMatches: function(matches) {
            return this._filterMatchesByScore(matches);
        },

        _filterMatchesByScore: function(matches) {
            if (this.checkScore) {
                matches = matches.filter(function(match) {
                    return match.score <= this.checkScore;
                }.bind(this));
            }
            return matches;
        },

        _newSearchEngine: function(str) {
            return new Fuse([str], this.engineOptions);
        }
    };

    return FuzzySearch;
});
