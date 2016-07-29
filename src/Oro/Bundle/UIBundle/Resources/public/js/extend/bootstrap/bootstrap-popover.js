define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    require('bootstrap');
    require('./bootstrap-tooltip');

    var Tooltip = $.fn.tooltip.Constructor;
    var Popover = $.fn.popover.Constructor;

    _.extend(Popover.prototype, _.pick(Tooltip.prototype,
        ['show', 'hide', 'applyPlacement', 'correctPlacement', 'replaceArrow', 'destroy']));

    Popover.prototype.arrow = function() {
        this.$arrow = this.$arrow || this.tip().find('.arrow');
        return this.$arrow;
    };

    Popover.prototype.tip = function() {
        if (!this.$tip) {
            this.$tip = $(this.options.template);
            var addClass = this.$element.data('class');
            if (addClass && !this.$tip.hasClass(addClass)) {
                this.$tip.addClass(addClass);
            }
        }
        return this.$tip;
    };

    Popover.prototype.hide = _.wrap(Popover.prototype.hide, function(func) {
        clearInterval(this.trackPositionInterval);
        return func.apply(this, _.rest(arguments));
    });
});
