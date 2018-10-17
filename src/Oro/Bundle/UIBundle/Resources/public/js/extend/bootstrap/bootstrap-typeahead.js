define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');

    require('bootstrap-typeahead');

    /**
     * This customization allows to define own functions for Typeahead
     */
    var Typeahead;
    var origTypeahead = $.fn.typeahead.Constructor;
    var origFnTypeahead = $.fn.typeahead;

    var typeaheadPatches = {
        show: function() {
            // fix for dropdown menu position that placed inside scrollable containers
            var pos = $.extend({}, this.$element.position(), {
                height: this.$element[0].offsetHeight
            });

            this.$menu
                .insertAfter(this.$element)
                .css({
                    top: pos.top + pos.height + this.scrollOffset(this.$element),
                    left: pos.left
                })
                .show();

            this.shown = true;
            return this;
        },
        scrollOffset: function($el) {
            // calculates additional offset of all scrolled on parents, except body and html
            var offset = 0;
            var stopProcess = false;

            $el.parents().each(function(i, el) {
                if (el !== document.body && el !== document.html && !stopProcess) {
                    offset += el.scrollTop;
                    stopProcess = $(el).css('position') === 'relative';
                }
            });

            return offset;
        },
        keypress: function() {
            // do nothing;
        }
    };

    Typeahead = function(element, options) {
        var opts = $.extend({}, $.fn.typeahead.defaults, typeaheadPatches, options);

        _.each(opts, function(value, name) {
            this[name] = value || this[name];
        }, this);

        this.$holder = $(opts.holder || '');

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
