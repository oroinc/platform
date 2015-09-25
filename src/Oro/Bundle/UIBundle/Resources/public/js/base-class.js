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

    BaseClass.prototype = {};

    _.extend(BaseClass.prototype, Backbone.Events);

    BaseClass.extend = Backbone.Model.extend;

    return BaseClass;
});
