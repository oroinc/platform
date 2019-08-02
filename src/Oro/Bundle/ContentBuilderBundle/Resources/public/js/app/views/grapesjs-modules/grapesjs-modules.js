define(function(require) {
    'use strict';

    var StyleManagerModule = require('orocontentbuilder/js/app/views/grapesjs-modules/style-manager-module');

    var GrapesJSModules;

    GrapesJSModules = _.extend({
        namespace: '-module',

        getModule: function(name) {
            if (!this[name + this.namespace]) {
                return;
            }
            return this[name + this.namespace];
        }
    }, {
        'style-manager-module': StyleManagerModule
    });

    return GrapesJSModules;
});
