define(function(require) {
    'use strict';

    var $ = require('jquery');
    require('bootstrap');

    var Tooltip = $.fn.tooltip.Constructor;

    var delegateAction = function(method, action) {
        return function() {
            var type = this.type;
            // Tooltip/Popover delegates initialization to element -- propagate action them first
            if (this.options.selector) {
                this.$element.find(this.options.selector).each(function() {
                    if ($(this).data(type)) {
                        $(this).popover(action);
                    }
                });
            }
            // clear timeout if it exists
            if (this.timeout) {
                clearTimeout(this.timeout);
                delete this.timeout;
            }
            return method.apply(this, arguments);
        };
    };

    Tooltip.prototype.hide = delegateAction(Tooltip.prototype.hide, 'hide');
    Tooltip.prototype.destroy = delegateAction(Tooltip.prototype.destroy, 'destroy');
});
