define(function(require) {
    'use strict';

    var $ = require('jquery');
    require('bootstrap');

    /**
     * This customization allows to define own functions for Typeahead
     */
    var Typeahead;
    var origTypeahead = $.fn.typeahead.Constructor;
    var origFnTypeahead = $.fn.typeahead;

    Typeahead = function(element, options) {
        var _this = this;
        var opts = $.extend({}, $.fn.typeahead.defaults, options);

        _.each(opts, function(value, name) {
            _this[name] = value || _this[name];
        });

        origTypeahead.apply(this, arguments);
    };

    Typeahead.prototype = origTypeahead.prototype;
    Typeahead.prototype.constructor = Typeahead;

    $.fn.typeahead = function(option) {
        return this.each(function() {
            var $this = $(this);
            var data = $this.data('typeahead');
            var options = typeof option === 'object' && option;
            if (!data) {
                $this.data('typeahead', (data = new Typeahead(this, options)));
            }
            if (typeof option === 'string') {
                data[option]();
            }
        });
    };

    $.fn.typeahead.defaults = origFnTypeahead.defaults;
    $.fn.typeahead.Constructor = Typeahead;
    $.fn.typeahead.noConflict = origFnTypeahead.noConflict;
});
