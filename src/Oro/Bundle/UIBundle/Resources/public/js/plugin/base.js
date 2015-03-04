define(function (require) {
    'use strict';

    var _ = require('underscore'),
        Backbone = require('backbone');

    function BasePlugin(main, manager, options) {
        this.main = main;
        this.manager = manager;
        this.options = options;
        this.initialize(main, options);
    }

    _.extend(BasePlugin.prototype, Backbone.Events, {
        initialize: function (main, options) {},
        enable: function () {
            this.enabled = true;
            this.trigger('enabled');
        },
        disable: function () {
            this.enabled = false;
            this.stopListening();
            this.trigger('disabled');
        },
        dispose: function () {
            if (this.disposed) {
                return;
            }
            this.disposed = true;
            this.trigger('disposed');
            this.off();
            this.stopListening();
            for (var prop in this) {
                if (this.hasOwnProperty(prop)) {
                    delete this[prop];
                }
            }
            this.disposed = true;
            return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
        }
    });

    BasePlugin.extend = Backbone.Model.extend;

    return BasePlugin;
});
