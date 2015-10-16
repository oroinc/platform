/** @lends StdClass */
define(function(require) {
    'use strict';

    var _ = require('underscore');
    var Backbone = require('backbone');

    /**
     * Base class that implement extending in backbone way.
     * Also connects events API by default
     *
     * @class
     * @exports StdClass
     */
    var BaseClass = function() {
        this.initialize.apply(this, arguments);
    };

    BaseClass.prototype = {
        initialize: function(options) {
            if (options.events) {
                this.on(options.events);
            }
        },
        dispose: function() {
            this.stopListening();
            delete this._events;
        }
    };

    _.extend(BaseClass.prototype, Backbone.Events);

    BaseClass.extend = Backbone.Model.extend;

    return BaseClass;
});
