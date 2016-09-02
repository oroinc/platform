define(function(require) {
    'use strict';

    var $ = require('jquery');
    var tools = require('oroui/js/tools');
    var console = window.console;

    var appearanceBuilder = {
        /**
         * Prepares and preloads all required class implementations for specified appearances
         *
         * @param {jQuery.Deferred} deferred
         * @param {Object} options
         * @param {jQuery} [options.$el] container for the grid
         * @param {string} [options.gridName] grid name
         * @param {Object} [options.gridPromise] grid builder's promise
         * @param {Object} [options.data] data for grid's collection
         * @param {Object} [options.metadata] configuration for the grid
         */
        processDatagridOptions: function(deferred, options) {
            if (tools.isMobile() || !options.metadata.options.appearances ||
                !options.metadata.options.appearances.length) {
                deferred.resolve();
                return;
            }
            $.when.apply($, this.prepareAppearances(options.metadata.options.appearances, options))
                .done(function() {
                    deferred.resolve();
                }).fail(function(e) {
                    if (console && console.error) {
                        console.log(e);
                        console.error('Apperance options loading failed. Reason: ' + e.message);
                    } else {
                        throw e;
                    }
                    deferred.resolve();
                });
            return deferred;
        },

        /**
         * Init() function is required
         */
        init: function(deferred, options) {
            deferred.resolve();
        },

        /**
         * Prepares appearance configuration
         *
         * @param {Array} appearances - appearances to prepare
         * @param gridConfiguration - datagrid configuration
         * @return {*}
         */
        prepareAppearances: function(appearances, gridConfiguration) {
            return appearances.map(function(appearance) {
                if (!appearance.plugin) {
                    return;
                }
                return tools.loadModuleAndReplace(appearance, 'plugin').then(function() {
                    return appearance.plugin.processMetadata(appearance, gridConfiguration);
                });
            });
        }
    };

    return appearanceBuilder;
});
