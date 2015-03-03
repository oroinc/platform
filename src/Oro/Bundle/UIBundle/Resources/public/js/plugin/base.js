define(function (require) {
    'use strict';

    var _ = require('underscore'),
        Backbone = require('backbone');

    function BasePlugin(main, options) {
        this.main = main;
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
            this.trigger('disabled');
        },
        dispose: function () {
            this.trigger('disposed');
            this.off();
            this.stopListening();
        }
    });

    BasePlugin.extend = Backbone.Model.extend;

    return BasePlugin;
});
