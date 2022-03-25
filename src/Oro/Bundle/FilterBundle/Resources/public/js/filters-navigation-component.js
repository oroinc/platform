import BaseComponent from 'oroui/js/app/components/base/component';
import FiltersIterator from 'orofilter/js/filters-iterator';
import KEYBOARD_CODES from 'oroui/js/tools/keyboard-key-codes';
import _ from 'underscore';
import 'jquery-ui/tabbable';

const FiltersNavigationComponent = BaseComponent.extend({
    /**
     * @inheritdoc
     */
    constructor: function FiltersNavigationComponent(options) {
        FiltersNavigationComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        this.options = options || {};

        if (!this.options.filters) {
            throw new Error('Option "filters" is required');
        }

        const filters = Object.values(this.options.filters);

        FiltersNavigationComponent.__super__.initialize.call(this, options);

        if (filters.length === 0) {
            // no filters in list -- nothing to do
            return;
        }

        this.onFilterVisibilityChange = _.debounce(this.onFilterVisibilityChange.bind(this), 50);

        const filterListeners = {
            keydownOnToggle: this.onFilterKeyDownToggle,
            focusinOnToggle: this.onFilterFocusInToggle,
            focusoutOnToggle: this.onFilterFocusOutToggle,
            enable: this.onFilterVisibilityChange,
            disable: this.onFilterVisibilityChange,
            showCriteria: this.setFilterAsCurrent
        };

        filters.forEach(filter => this.listenTo(filter, filterListeners));

        this.iterator = new FiltersIterator(filters);
        this.listenTo(this.iterator, 'change:index', this.updateTabIndex);

        this.setFirstVisibleFilterAsCurrent();
    },

    onFilterKeyDownToggle(e) {
        if (this.disposed) {
            return;
        }

        switch (e.keyCode) {
            case KEYBOARD_CODES.ARROW_LEFT:
                this.focusPreviousFilter();
                e.preventDefault();
                break;
            case KEYBOARD_CODES.ARROW_RIGHT:
                this.focusNextFilter();
                e.preventDefault();
                break;
            case KEYBOARD_CODES.ENTER:
            case KEYBOARD_CODES.SPACE:
            case KEYBOARD_CODES.ARROW_UP:
            case KEYBOARD_CODES.ARROW_DOWN:
                this.openCurrentFilter();
                e.preventDefault();
                break;
            case KEYBOARD_CODES.ESCAPE:
                this.closeCurrentFilterAndSetFocus();
                e.preventDefault();
                e.stopPropagation();
                break;
        }
    },

    onFilterFocusInToggle(e) {
        e.target.classList.add('focus-via-arrows-keys');
    },

    onFilterFocusOutToggle(e) {
        e.target.classList.remove('focus-via-arrows-keys');
    },

    onFilterVisibilityChange() {
        const filter = this.iterator.current();
        if (!filter.renderable || !filter.visible) {
            this.setFirstVisibleFilterAsCurrent();
        }
        this.updateTabIndex();
    },

    setFilterAsCurrent(filter) {
        this.iterator.setCurrent(filter);
    },

    updateTabIndex() {
        const currentFilter = this.iterator.current();
        currentFilter.getCriteriaSelector().attr('tabindex', 0);
        this.iterator.filters.forEach(filter => {
            if (filter !== currentFilter) {
                filter.getCriteriaSelector().attr('tabindex', -1);
            }
        });
    },

    getFilterByIterator(iteratorMethod) {
        const currentFilter = this.iterator.current();

        let filter;
        do {
            filter = this.iterator[iteratorMethod]();
        } while (
            (!filter.renderable || !filter.visible) &&
            filter !== currentFilter // if the filter is currentFilter -- all filters are checked and it is second lap
        );

        return filter;
    },

    setFirstVisibleFilterAsCurrent() {
        const filter = this.iterator.reset();
        if (!filter.visible || !filter.renderable) {
            this.getFilterByIterator(_.isRTL() ? 'previous' : 'next');
        }
    },

    focusNextFilter() {
        const filter = this.getFilterByIterator(_.isRTL() ? 'previous' : 'next');

        _.result(filter, 'focusCriteriaToggler');
    },

    focusPreviousFilter() {
        const filter = this.getFilterByIterator(_.isRTL() ? 'next' : 'previous');

        _.result(filter, 'focusCriteriaToggler');
    },

    openCurrentFilter() {
        const filter = this.iterator.current();

        _.result(filter, '_showCriteria');
    },

    closeCurrentFilterAndSetFocus() {
        const filter = this.iterator.current();

        _.result(filter, '_hideCriteria');
        _.result(filter, 'focusCriteriaToggler');
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        delete this.iterator;
        FiltersNavigationComponent.__super__.dispose.call(this);
    }
});

export default FiltersNavigationComponent;
