class GridRowsCounter {
    constructor(grid) {
        if (grid === void 0) {
            throw new Error('Option "grid" is required.');
        }

        this.grid = grid;
    }

    /**
     * Get a number of rows in the grid including header and footer
     *
     * @return {number}
     */
    getGridRowsCount() {
        return this.getHeaderRowsCount() + this.getTotalRowsCount() + this.getFooterRowsCount();
    }

    /**
     * Get a number of rows in grid's header
     *
     * @return {number}
     */
    getHeaderRowsCount() {
        return this.grid.subview('header') ? this.grid.subview('header').getRowsCount() : 0;
    }

    /**
     * Get a number of total rows in a grid (all pages)
     *
     * @return {number}
     */
    getTotalRowsCount() {
        return this.grid.collection.filter(model => model.isAuxiliary !== true).length;
    }

    /**
     * Get a number of rows in grid's footer
     *
     * @return {number}
     */
    getFooterRowsCount() {
        return this.grid.subview('footer') ? this.grid.subview('footer').getRowsCount() : 0;
    }
}

export default GridRowsCounter;
