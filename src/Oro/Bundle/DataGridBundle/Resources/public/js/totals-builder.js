const initHandler = function(collection) {
    collection.on('beforeReset', function(collection, models, options) {
        collection.state.totals = options.totals;
    });
};

export default {
    /**
     * Builder interface implementation
     *
     * @param {jQuery.Deferred} deferred
     * @param {Object} options
     * @param {jQuery} [options.$el] container for the grid
     * @param {string} [options.gridName] grid name
     * @param {Object} [options.gridPromise] grid builder's promise
     * @param {Object} [options.data] data for grid's collection
     * @param {Object} [options.metadata] configuration for the grid
     */
    init: function(deferred, options) {
        options.gridPromise.done(function(grid) {
            initHandler(grid.collection);
            deferred.resolve();
        }).fail(function() {
            deferred.reject();
        });
    }
};
