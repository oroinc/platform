define(function (require) {
    'use strict';

    var Backbone = require('backbone');

    function BasePlugin(main, options) {
        this.initialize(main, options);
    }

    BasePlugin.prototype = {
        initialize: function (main, options) {},
        enable: function () {},
        disable: function () {},
        dispose: function () {}
    };

    BasePlugin.extend = Backbone.Model.extend;

    return BasePlugin;
});
