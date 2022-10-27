define(function(require) {
    'use strict';

    const FiltersIterator = require('orofilter/js/filters-iterator').default;
    const filtersMock = {
        first: {name: 'first'},
        second: {name: 'second'},
        third: {name: 'third'}
    };

    describe('orofilter/js/filters-iterator', function() {
        let filtersIterator;
        let indexChangeHandler;

        beforeEach(function() {
            filtersIterator = new FiltersIterator(Object.values(filtersMock));
            indexChangeHandler = jasmine.createSpy();
            filtersIterator.on('change:index', indexChangeHandler);
        });

        it('check constructor required arguments', function() {
            expect(() => new FiltersIterator()).toThrowError('Option "filters" is required');
            expect(() => new FiltersIterator({})).toThrowError('Option "filters" is required');
            expect(() => new FiltersIterator([])).toThrowError('Option "filters" is required');

            expect(() => new FiltersIterator([{name: 'first'}])).not.toThrowError('Option "filters" is required');
        });

        it('check initialized state', function() {
            const filter = filtersIterator.current();

            expect(filter).toBe(filtersMock.first);
            expect(indexChangeHandler).not.toHaveBeenCalled();
        });

        it('check "next" method', function() {
            expect(filtersIterator.next()).toBe(filtersMock.second);
            expect(indexChangeHandler.calls.mostRecent().args).toEqual([1]);

            expect(filtersIterator.next()).toBe(filtersMock.third);
            expect(indexChangeHandler.calls.mostRecent().args).toEqual([2]);

            expect(filtersIterator.next()).toBe(filtersMock.first);
            expect(indexChangeHandler.calls.mostRecent().args).toEqual([0]);
        });

        it('check "previous" method', function() {
            expect(filtersIterator.previous()).toBe(filtersMock.third);
            expect(indexChangeHandler.calls.mostRecent().args).toEqual([2]);
            expect(filtersIterator.previous()).toBe(filtersMock.second);
            expect(indexChangeHandler.calls.mostRecent().args).toEqual([1]);
            expect(filtersIterator.previous()).toBe(filtersMock.first);
            expect(indexChangeHandler.calls.mostRecent().args).toEqual([0]);
        });

        it('check "first" method', function() {
            filtersIterator.next();
            expect(indexChangeHandler.calls.mostRecent().args).toEqual([1]);

            expect(filtersIterator.first()).toBe(filtersMock.first);
            expect(indexChangeHandler.calls.mostRecent().args).toEqual([0]);
        });

        it('check "last" method', function() {
            expect(filtersIterator.last()).toBe(filtersMock.third);
            expect(indexChangeHandler.calls.mostRecent().args).toEqual([2]);
        });

        it('check "reset" method', function() {
            filtersIterator.next();
            expect(indexChangeHandler.calls.mostRecent().args).toEqual([1]);

            expect(filtersIterator.reset()).toBe(filtersMock.first);
            expect(indexChangeHandler.calls.mostRecent().args).toEqual([0]);

            expect(filtersIterator.current()).toBe(filtersMock.first);
        });

        it('check "getFilterByIndex" method', function() {
            expect(filtersIterator.getFilterByIndex(2)).toBe(filtersMock.third);
            expect(indexChangeHandler.calls.mostRecent().args).toEqual([2]);

            expect(filtersIterator.getFilterByIndex(999)).toBe(filtersMock.first);
            expect(indexChangeHandler.calls.mostRecent().args).toEqual([0]);
        });
    });
});
