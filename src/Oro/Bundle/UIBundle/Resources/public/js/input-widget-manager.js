define(function(require) {
    'use strict';

    var InputWidgetManager;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    var $ = require('jquery');
    var _ = require('underscore');

    /**
     * InputWidgetManager used to register input widgets and create widget for applicable inputs.
     *
     * Example of usage:
     *     InputWidgetManager.create($(':input'));//create widgets for inputs
     *
     *     //add widget to InputWidgetManager
     *     InputWidgetManager.addWidget('uniform-select', {
     *         priority: 10,
     *         tagName: 'SELECT',
     *         selector: 'select:not(.no-uniform)',
     *         Widget: UniformSelectInputWidget
     *     });
     *
     *     'uniform-select' - unique widget key
     *     `tagName` - required. Widget will be used only for inputs with the same tag name.
     *     `Widget` - required. InputWidget constructor
     *     `selector` - additional filter for input elements
     *     `priority` - for each input will be used first applicable widget.
     *     You can control the order of the widgets, if in the InputWidgetManager registered several widgets with theme same `tagName`.
     *
     *     //or you can remove widget from InputWidgetManager
     *     InputWidgetManager.removeWidget('uniform-select')
     */
    InputWidgetManager = {
        noWidgetSelector: '.no-input-widget',

        addWidgets: [],

        removeWidgets: [],

        widgets: {},

        widgetsByTag: {},

        /**
         * @param {String} key
         * @param {Object} widget
         */
        addWidget: function(key, widget) {
            _.defaults(widget, {
                key: key,
                priority: 10,
                tagName: '',
                selector: '',
                Widget: null
            });

            this.addWidgets.push(widget);
        },

        /**
         * @param {String} key
         */
        removeWidget: function(key) {
            this.removeWidgets.push(key);
        },

        /**
         * Execute addWidgets and removeWidgets queue, then rebuild widgetsByTag.
         */
        collectWidgets: function() {
            var self = this;
            var rebuild = false;

            if (self.addWidgets.length) {
                rebuild = true;
                _.each(self.addWidgets, function(widget) {
                    self.widgets[widget.key] = widget;
                });
                self.addWidgets = [];
            }

            if (self.removeWidgets.length) {
                rebuild = true;
                _.each(self.removeWidgets, function(key) {
                    delete self.widgets[key];
                });
                self.removeWidgets = [];
            }

            if (rebuild) {
                self.widgetsByTag = {};
                _.each(_.sortBy(self.widgets, 'priority'), function(widget) {
                    if (!widget.tagName ||
                        !_.isFunction(widget.Widget) ||
                        !(widget.Widget.prototype instanceof AbstractInputWidget)) {
                        return;
                    }
                    if (!self.widgetsByTag[widget.tagName]) {
                        self.widgetsByTag[widget.tagName] = [];
                    }
                    self.widgetsByTag[widget.tagName].push(widget);
                });
            }
        },

        /**
         * Walk by each input and create widget for applicable inputs without widget
         *
         * @param {jQuery} $inputs
         */
        create: function($inputs) {
            var self = this;
            self.collectWidgets();

            _.each($inputs, function(input) {
                var $input = $(input);
                if (self.hasWidget($input)) {
                    return ;
                }

                var widgets = self.widgetsByTag[$input.prop('tagName')] || [];
                _.each(widgets, function(widget) {
                    if (!self.hasWidget($input) && self.isApplicable($input, widget)) {
                        self.createWidget($input, widget.Widget, {});
                    }
                });
            });
        },

        /**
         * @param {jQuery} $input
         * @param {Object} widget
         * @returns {boolean}
         */
        isApplicable: function($input, widget) {
            if (this.noWidgetSelector && $input.is(this.noWidgetSelector)) {
                return false;
            } else if (widget.selector && !$input.is(widget.selector)) {
                return false;
            }
            return true;
        },

        /**
         * @param {jQuery} $input
         * @param {AbstractInputWidget} Widget
         * @param {Object} options
         * @returns {AbstractInputWidget}
         */
        createWidget: function($input, Widget, options) {
            if (!options) {
                options = {};
            }
            options.$el = $input;
            return new Widget(options);
        },

        /**
         * @param {jQuery} $input
         * @returns {boolean}
         */
        hasWidget: function($input) {
            return this.getWidget($input) ? true : false;
        },

        /**
         * @param {jQuery} $input
         * @returns {AbstractInputWidget|null}
         */
        getWidget: function($input) {
            var widget = $input.data('inputWidget');
            return widget ? widget : null;
        }
    };

    $.fn.extend({
        /**
         * This is an jQuery API for InputWidgetManager or InputWidget.
         * If the first argument is "create" - will be executed `InputWidgetManager.create` function.
         * If the first argument is absent - will be returned InputWidget instance or `false`.
         * Otherwise will be executed `InputWidget[command]` function for each element.
         *
         * Example of usage:
         *     $(':input').inputWidget('create');//create widgets
         *     $(':input').inputWidget('refresh');//update widget, for example after input value change
         *     $(':input:first').inputWidget('getContainer');//get widget root element
         *     $(':input').inputWidget('setWidth', 100);//set widget width
         *     $(':input').inputWidget('dispose');//destroy widgets and dispose widget instance
         *
         * @param {String|null} command
         * @returns {mixed}
         */
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
