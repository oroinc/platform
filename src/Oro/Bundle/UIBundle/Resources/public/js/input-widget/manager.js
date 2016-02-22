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

        initialize: function($inputs) {
            var self = this;
            _.each($inputs, function(input) {
                var $input = $(input);
                if ($input.inputWidget()) {
                    return ;
                }

                var widgets = self.widgetsByTag[$input.prop('tagName')] || [];
                _.each(widgets, function(widget) {
                    if (!$input.inputWidget() && self.isApplicable(widget, $input)) {
                        $input.inputWidget('create', widget.widget);
                    }
                });
            });
        },

        destroy: function($inputs) {
            _.each($inputs, function(input) {
                var $input = $(input);
                $input.inputWidget('destroy');
            });
        },

        isApplicable: function(widget, $input) {
            if (widget.tagName !== $input.prop('tagName')) {
                return false;
            } else if (widget.selector && !$input.is(widget.selector)) {
                return false;
            }
            return true;
        }
    };

    return InputWidgetManager;
});
