import {uniq} from 'underscore';
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

        const tableClassName = [];
        if (options.themeOptions.tableClassName) {
            tableClassName.push(...options.themeOptions.tableClassName.split(' '));
        }

        tableClassName.push('grid-row-link-enabled');
        options.themeOptions.tableClassName = uniq(tableClassName).join(' ');

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
