define(function(require) {
    'use strict';

    const FuzzySearch = require('oroui/js/fuzzy-search');

    describe('oroui/js/fuzzy-search', function() {
        it('check isMatched', function() {
            expect(FuzzySearch.isMatched('Localization', 'locl')).toBeTruthy();
            expect(FuzzySearch.isMatched('Localization', 'loclux')).toBeFalsy();
            expect(FuzzySearch.isMatched('Localization', 'lcl')).toBeFalsy();
        });

        it('check getMatches', function() {
            expect(FuzzySearch.getMatches('Localization', 'locl')).toEqual([{
                item: 'Localization',
                refIndex: 0,
                score: 0.25
            }]);
            expect(FuzzySearch.getMatches('Localization', 'loclux')).toEqual([]);
        });

        it('check cache', function() {
            FuzzySearch.isMatched('Localization', 'lcl');
            expect(FuzzySearch._cache.hasOwnProperty('Localization')).toBeTruthy();
            FuzzySearch.clearCache();
            expect(FuzzySearch._cache).toEqual({});
        });
    });
});
