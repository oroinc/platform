define(function(require) {
    'use strict';

    var StyleManagerModule = require('orocontentbuilder/js/app/views/grapesjs-modules/style-manager-module');
    var PanelManagerModule = require('orocontentbuilder/js/app/views/grapesjs-modules/panels-module');
    var ComponentsModule = require('orocontentbuilder/js/app/views/grapesjs-modules/grapesjs-components');
    var _ = require('underscore');

    var GrapesJSModules;

    /**
     * Create GrapesJS module manager
     * @type {*|void}
     */
    GrapesJSModules = _.extend({
        /**
         * Nodule namespace
         * @property {String}
         */
        namespace: '-module',

        /**
         * Call module method
         * @param name
         * @param options
         */
        call: function(name, options) {
            if (!this[name + this.namespace] || !_.isFunction(this[name + this.namespace])) {
                return;
            }

            return new this[name + this.namespace](options);
        },

        /**
         * Get module by name
         * @param name
         * @returns {*}
         */
        getModule: function(name) {
            if (!this[name + this.namespace]) {
                return;
            }
            return this[name + this.namespace];
        }
    }, {
        'style-manager-module': StyleManagerModule,
        'panel-manager-module': PanelManagerModule,
        'components-module': ComponentsModule
    });

    return GrapesJSModules;
});
