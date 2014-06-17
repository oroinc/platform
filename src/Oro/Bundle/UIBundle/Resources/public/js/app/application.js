/*global define*/
define([
    'chaplin'
], function (Chaplin) {
    'use strict';

    var Application = Chaplin.Application.extend({
        initialize: function (options) {
            this.options = options || {};
            Chaplin.mediator.setHandler('retrieveOption', this.retrieveOption, this);
            Chaplin.Application.prototype.initialize.apply(this, arguments);
        },

        retrieveOption: function (prop) {
            return this.options.hasOwnProperty(prop) ? this.options[prop] : void 0;
        }
    });

    return Application;
});
