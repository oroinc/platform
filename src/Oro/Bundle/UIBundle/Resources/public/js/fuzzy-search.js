define(function(require) {
    'use strict';

    var FuzzySearch;
    var Fuse = require('Fuse');
    var _ = require('underscore');

    var config = _.extend({
        checkScore: 0.49,
        engineOptions: {
            includeScore: true
        }
    }, require('module').config() || {});

    FuzzySearch = {
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
            var cache = this._cache[str];
            if (!cache) {
                cache = this._cache[str] = {
                    searchEngine: this._newSearchEngine(str),
                    queries: {}
                };
            }

            if (cache.queries[query] === undefined) {
                var matches = cache.searchEngine.search(query);
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
