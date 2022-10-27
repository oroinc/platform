import loadModules from 'oroui/js/app/services/load-modules';

const htmlTemplatesPreloader = {
    /**
     * Prepares and preloads all required templates for html-template cell type
     *
     * @param {jQuery.Deferred} deferred
     * @param {Object} options
     * @param {Object} [options.metadata] configuration for the grid
     * @param {Array<Object>} [options.metadata.columns] list of columns definition
     */
    processDatagridOptions: function(deferred, options) {
        const promises = options.metadata.columns
            .filter(column => column.frontend_template)
            .map(column => loadModules(column.frontend_template).then(template => {
                column.template = template;
                delete column.frontend_template;
            }));

        if (promises.length) {
            Promise.all(promises).then(() => deferred.resolve());
        } else {
            deferred.resolve();
        }
        return deferred;
    },

    /**
     * Init() function is required
     */
    init: function(deferred, options) {
        deferred.resolve();
    }
};

export default htmlTemplatesPreloader;
