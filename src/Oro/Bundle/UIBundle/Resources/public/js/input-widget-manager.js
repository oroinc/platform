define(function(require) {
    'use strict';

    var InputWidgetManager;
    var $ = require('jquery');
    var _ = require('underscore');

    InputWidgetManager = {
        widgetsByTag: {},

        registerWidget: function(widget) {
            _.defaults(widget, {
                tagName: '',
                selector: '',
                widget: null
            });

            if (!this.widgetsByTag[widget.tagName]) {
                this.widgetsByTag[widget.tagName] = [];
            }

            this.widgetsByTag[widget.tagName].push(widget);
        },

        create: function($inputs) {
            var self = this;
            _.each($inputs, function(input) {
                var $input = $(input);
                if (self.hasWidget($input)) {
                    return ;
                }

                var widgets = self.widgetsByTag[$input.prop('tagName')] || [];
                _.each(widgets, function(widget) {
                    if (!self.hasWidget($input) && self.isApplicable($input, widget)) {
                        self.createWidget($input, widget.widget);
                    }
                });
            });
        },

        isApplicable: function($input, widget) {
            return !widget.selector || $input.is(widget.selector);
        },

        createWidget: function($input, Widget) {
            return new Widget({
                $el: $input
            });
        },

        hasWidget: function($input) {
            return this.getWidget($input) ? true : false;
        },

        getWidget: function($input) {
            var widget = $input.prop('inputWidget');
            return widget ? widget : null;
        }
    };

    $.fn.extend({
        inputWidget: function(command) {
            if (command === 'create') {
                return InputWidgetManager.create(this);
            }

            var response = null;
            var args = Array.prototype.slice.call(arguments, 1);
            this.each(function() {
                var $input = $(this);
                var widget = InputWidgetManager.getWidget($input);

                if (!command) {
                    response = widget ? widget : false;
                } else if (widget) {
                    if (!widget[command]) {
                        throw new Error('Input widget doesn\'t support command "' + command + '"');
                    }
                    response = widget[command].apply(widget, args);
                }
            });

            return response;
        }
    });

    return InputWidgetManager;
});
