define(function(require) {
    'use strict';

    var StyleManagerModule = require('orocontentbuilder/js/app/views/grapesjs-modules/style-manager-module');
    var PanelManagerModule = require('orocontentbuilder/js/app/views/grapesjs-modules/panels-module');
    var _ = require('underscore');

    var GrapesJSModules;

    GrapesJSModules = _.extend({
        namespace: '-module',

        call: function(name, options) {
            if (!this[name + this.namespace] || !_.isFunction(this[name + this.namespace])) {
                return;
            }

            return new this[name + this.namespace](options);
        },

        getModule: function(name) {
            if (!this[name + this.namespace]) {
                return;
            }
            return this[name + this.namespace];
        }
    }, {
        'style-manager-module': StyleManagerModule,
        'panel-manager-module': PanelManagerModule
    });

    return GrapesJSModules;
});
