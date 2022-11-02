import {Events} from 'backbone';

const FiltersIterator = class {
    constructor(filters) {
        if (!filters || !Array.isArray(filters) || !filters.length) {
            throw new Error('Option "filters" is required');
        }

        this.filters = filters;
        this._index = 0;
    }

    get index() {
        return this._index;
    }

    set index(index) {
        this._index = index;
        this.trigger('change:index', index);
    }

    next() {
        if (this.index + 1 < this.filters.length) {
            return this.getFilterByIndex(this.index + 1);
        }

        return this.first();
    }

    previous() {
        if (this.index > 0) {
            return this.getFilterByIndex(this.index - 1);
        }

        return this.last();
    }

    current() {
        return this.filters[this.index];
    }

    setCurrent(filter) {
        const index = this.filters.indexOf(filter);
        this.index = index !== -1 ? index : 0;
    }

    first() {
        return this.getFilterByIndex(0);
    }

    last() {
        return this.getFilterByIndex(this.filters.length - 1);
    }

    getFilterByIndex(index) {
        const filter = this.filters[index];

        if (filter) {
            this.index = index;
            return filter;
        }

        return this.first();
    }

    reset() {
        this.index = 0;
        return this.current();
    }
};

Object.assign(FiltersIterator.prototype, Events);

export default FiltersIterator;
