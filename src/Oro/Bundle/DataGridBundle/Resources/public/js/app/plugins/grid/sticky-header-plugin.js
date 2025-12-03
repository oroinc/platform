import BasePlugin from 'oroui/js/app/plugins/base/plugin';

const StickyHeaderPlugin = BasePlugin.extend({
    events: {
        shown: 'onGridShown'
    },

    initialize() {
        this.selectors = {
            body: '.datagrid-content',
            content: '.grid-scrollable-container',
            scrollableContainer: '.scrollable-container',
            dialogScrollableContainer: '.ui-dialog-content',
            thead: '.grid-header',
            headerCells: '.grid-header-cell',
            osViewport: '.os-viewport'
        };
        this.classes = {
            header: 'datagrid-header',
            sticky: 'datagrid-sticky',
            stickyTable: 'datagrid-table',
            isStuck: 'stuck',
            cloneThead: 'datagrid-thead-invisible'
        };
        this.delegateEvents();
    },

    delegateEvents() {
        StickyHeaderPlugin.__super__.delegateEvents.call(this);

        this.listenTo(this.main, 'content:update', this.updateCloneThead);

        this.resizeObserver = new ResizeObserver(this.resizeObserverCallback.bind(this));

        return this;
    },

    undelegateEvents() {
        StickyHeaderPlugin.__super__.undelegateEvents.call(this);

        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }

        delete this.resizeObserver;

        this.unsetIntersection();

        return this;
    },

    /**
     * Observe resizing of datagrid cells and sync these values with sticky header
     * @param {ResizeObserverEntry[]} entries
     */
    resizeObserverCallback(entries) {
        for (const entry of entries) {
            // Check if it is the grid container
            if (entry.target === this.domCache.content) {
                this.toggleSticky(entry.target);
                continue;
            }

            // Check if it is the grid cell
            if (Number.isFinite(entry.target.cellIndex)) {
                this.syncCellSize(entry.target);
            }
        }
    },

    onGridShown() {
        if (this.enabled && !this.connected) {
            this.enable();
        }
    },

    enable() {
        if (!this.main.rendered) {
            // not ready to apply stickyHeader
            StickyHeaderPlugin.__super__.enable.call(this);
            return;
        }

        this.setupCache();
        this.attachScroll();
        this.resizeObserver.observe(this.domCache.content);
        this.setIntersection();

        StickyHeaderPlugin.__super__.enable.call(this);
    },

    disable() {
        this.unsetSticky();

        StickyHeaderPlugin.__super__.disable.call(this);
    },

    setupCache() {
        const body = this.main.grid.closest(this.selectors.body);
        const scrollableContainer = this.main.grid.closest(this.selectors.scrollableContainer);
        const dialogScrollableContainer = this.main.grid.closest(this.selectors.dialogScrollableContainer);
        const content = this.main.grid.closest(this.selectors.content);
        const $content = this.main.$grid.closest(this.selectors.content);
        const thead = this.main.grid.querySelector(this.selectors.thead);
        const headerCells = thead.querySelectorAll(this.selectors.headerCells);
        const cloneThead = thead.cloneNode(true);
        const cloneHeaderCells = cloneThead.querySelectorAll(this.selectors.headerCells);
        const header = document.createElement('div');
        const sticky = document.createElement('div');
        const stickyTable = document.createElement('table');

        header.classList.add(this.classes.header);
        sticky.classList.add(this.classes.sticky);
        stickyTable.classList.add(this.classes.stickyTable);
        cloneThead.classList.add(this.classes.cloneThead);

        sticky.append(stickyTable);
        header.append(sticky);

        this.domCache = {
            dialogScrollableContainer,
            scrollableContainer,
            header,
            body,
            content,
            $content,
            thead,
            headerCells,
            cloneThead,
            cloneHeaderCells,
            sticky,
            stickyTable
        };
    },

    toggleSticky(target) {
        const {clientHeight, scrollHeight} = target;

        // Check if grid container has the scroll
        if (clientHeight === scrollHeight) {
            this.setSticky();
        } else {
            this.unsetSticky();
        }
    },

    setSticky() {
        if (!this.isStickySet()) {
            this.domCache.body.before(this.domCache.header);
            this.domCache.thead.after(this.domCache.cloneThead);
            this.domCache.stickyTable.append(this.domCache.thead);

            this.observeCells();
            this.setIntersection();
        }
    },

    unsetSticky() {
        if (this.isStickySet()) {
            this.unobserveCells();
            this.clearStylesOfCells(this.domCache.headerCells);
            this.domCache.cloneThead.replaceWith(this.domCache.thead);
            this.domCache.header.remove();
            this.setIntersection();
        }
    },

    isStickySet() {
        return this.domCache.header.isConnected;
    },

    setIntersection() {
        this.unsetIntersection();

        this.intersectionObserver = new IntersectionObserver(
            this.intersectionObserverCallback.bind(this),
            this.intersectionObserverOptions()
        );

        this.intersectionObserver.observe(this.domCache.header);
    },

    /**
     * Track when header is sticky
     * @param {IntersectionObserverEntry} entry
     */
    intersectionObserverCallback([entry]) {
        const toggle = this.isStickySet() && (entry.intersectionRatio < 1 && entry.intersectionRatio > 0);
        this.main.el.classList.toggle(this.classes.isStuck, toggle);
    },

    /**
     * Get options for Intersection Observer
     * @returns {Object} options
     */
    intersectionObserverOptions() {
        const top = parseInt(
            getComputedStyle(this.domCache.thead).getPropertyValue('--datagrid-sticky-offset')
        ) || 0;

        const root = [
            this.domCache.content,
            this.domCache.dialogScrollableContainer,
            this.domCache.scrollableContainer
        ].find(item => !!item && item.clientHeight !== item.scrollHeight) || null;

        return {
            root,
            rootMargin: `-${top + 1}px 0px 0px`,
            threshold: [1]
        };
    },

    unsetIntersection() {
        if (this.intersectionObserver) {
            this.intersectionObserver.disconnect();
            delete this.intersectionObserver;
        }
    },

    /**
     * Sync size of stuck cell with datagrid
     * @param {Element} cell
     */
    syncCellSize(cell) {
        const {width} = cell.getBoundingClientRect();
        const item = this.domCache.headerCells.item(cell.cellIndex);

        Object.assign(item.style, {
            width: `${width}px`,
            maxWidth: `${width}px`,
            minWidth: `${width}px`,
            boxSizing: 'border-box'
        });
    },

    attachScroll() {
        const scrollContent = this.domCache.content.querySelector(this.selectors.osViewport) ?? this.domCache.content;

        this.domCache.sticky.addEventListener('scroll', () => {
            scrollContent.scrollLeft = this.domCache.sticky.scrollLeft;
        }, {passive: true});

        scrollContent.addEventListener('scroll', () => {
            this.domCache.sticky.scrollLeft = scrollContent.scrollLeft;
        }, {passive: true});
    },

    observeCells() {
        if (this.resizeObserver) {
            this.domCache.cloneHeaderCells.forEach(item => this.resizeObserver.observe(item));
        }
    },

    unobserveCells() {
        if (this.resizeObserver) {
            this.domCache.cloneHeaderCells.forEach(item => this.resizeObserver.unobserve(item));
        }
    },

    updateCloneThead() {
        this.unobserveCells();

        this.domCache.headerCells = this.domCache.thead.querySelectorAll(this.selectors.headerCells);
        this.clearStylesOfCells(this.domCache.headerCells);
        const cloneThead = this.domCache.thead.cloneNode(true);
        cloneThead.classList.add(this.classes.cloneThead);

        this.domCache.cloneThead.replaceWith(cloneThead);
        this.domCache.cloneThead = cloneThead;
        this.domCache.cloneHeaderCells = this.domCache.cloneThead.querySelectorAll(this.selectors.headerCells);

        if (this.isStickySet()) {
            this.observeCells();
        }
    },

    clearStylesOfCells(cells) {
        cells.forEach(cell => cell.removeAttribute('style'));
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.undelegateEvents();

        StickyHeaderPlugin.__super__.dispose.call(this);
    }
});

export default StickyHeaderPlugin;
