import CellLinksPlugin from 'orodatagrid/js/app/plugins/grid/cell-links-plugin';

export default {
    processDatagridOptions(deferred, options) {
        if (!options.metadata.rowLinkEnabled) {
            deferred.resolve();
            return;
        }

        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }

        options.metadata.plugins.push(CellLinksPlugin);

        deferred.resolve();
    },

    /**
     * Init() function is required
     */
    init: function(deferred) {
        deferred.resolve();
    }
};
