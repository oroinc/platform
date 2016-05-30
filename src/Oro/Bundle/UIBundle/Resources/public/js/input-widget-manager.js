define(function(require) {
    'use strict';

    var InputWidgetManager;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var console = window.console;

    /**
     * InputWidgetManager used to register input widgets and create widget for applicable inputs.
     *
     * Example of usage:
     *     InputWidgetManager.create($(':input'));//create widgets for inputs
     *
     *     //add widget to InputWidgetManager
     *     InputWidgetManager.addWidget('uniform-select', {
     *         priority: 10,
     *         selector: 'select:not(.no-uniform)',
     *         Widget: UniformSelectInputWidget
     *     });
     *
     *     'uniform-select' - unique widget key
     *     `selector` - required. Widget will be used only for inputs applicable to this selector
     *     `Widget` - required. InputWidget constructor
     *     `priority` - you can control the order of the widgets. For each input will be used first applicable widget.
     *
     *     //or you can remove widget from InputWidgetManager
     *     InputWidgetManager.removeWidget('uniform-select')
     */
    InputWidgetManager = {
        noWidgetSelector: '.no-input-widget',

        addWidgets: [],

        removeWidgets: [],

        widgets: {},

        widgetsByPriority: {},

        /**
         * @param {String} key
         * @param {Object} widget
         */
        addWidget: function(key, widget) {
            _.defaults(widget, {
                key: key,
                priority: 10,
                selector: '',
                disableAutoCreate: false,
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
         * Execute addWidgets and removeWidgets queue, then rebuild widgetsByPriority.
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
                self.widgetsByPriority = [];
                _.each(_.sortBy(self.widgets, 'priority'), function(widget) {
                    if (!self.isValidWidget(widget)) {
                        self.error('Input widget "%s" is invalid', widget.key);
                        return;
                    }
                    self.widgetsByPriority.push(widget);
                });
            }
        },

        isValidWidget: function(widget) {
            return widget.selector &&
                _.isFunction(widget.Widget) &&
                widget.Widget.prototype instanceof AbstractInputWidget;
        },

        /**
         * Walk by each input and create widget for applicable inputs without widget
         *
         * @param {jQuery} $inputs
         * @param {String|null} widgetKey
         * @param {Object|null} options
         */
        create: function($inputs, widgetKey, options) {
            var self = this;
            self.collectWidgets();

            if (options || widgetKey) {
                //create new widget with options
                $inputs.inputWidget('dispose');
            }

            _.each($inputs, function(input) {
                var $input = $(input);
                if (self.hasWidget($input)) {
                    return ;
                }

                _.each(self.widgetsByPriority, function(widget) {
                    if (!self.hasWidget($input) && self.isApplicable($input, widget, widgetKey)) {
                        self.createWidget($input, widget.Widget, options || {});
                    }
                });
            });
        },

        /**
         * @param {jQuery} $input
         * @param {Object} widget
         * @param {String|null} widgetKey
         * @returns {boolean}
         */
        isApplicable: function($input, widget, widgetKey) {
            if (widgetKey && widget.key !== widgetKey) {
                return false;
            } else if (!widgetKey && widget.disableAutoCreate) {
                return false;
            } else if (this.noWidgetSelector && $input.is(this.noWidgetSelector)) {
                return false;
            } else if (widget.selector && !$input.is(widget.selector)) {
                return false;
            }
            return true;
        },

        /**
         * @param {jQuery} $input
         * @param {AbstractInputWidget|Function} Widget
         * @param {Object} options
         */
        createWidget: function($input, Widget, options) {
            if (!options) {
                options = {};
            }
            options.el = $input.get(0);
            var widget = new Widget(options);
            if (!widget.isInitialized()) {
                widget.dispose();
            }
        },

        /**
         * @param {jQuery} $input
         * @returns {boolean}
         */
        hasWidget: function($input) {
            return Boolean(this.getWidget($input));
        },

        /**
         * @param {jQuery} $input
         * @returns {AbstractInputWidget|null}
         */
        getWidget: function($input) {
            return $input.data('inputWidget') || null;
        },

        error: function() {
            if (tools.debug) {
                console.error.apply(console, arguments);
            }
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
         *     $(':input:first').inputWidget('container');//get widget root element
         *     $(':input').inputWidget('width', 100);//set widget width
         *     $(':input').inputWidget('dispose');//destroy widgets and dispose widget instance
         *
         * @param {String|null} command
         * @returns {mixed}
         */
        inputWidget: function(command) {
            var args = _.rest(arguments);
            if (command === 'create') {
                args.unshift(this);
                return InputWidgetManager.create.apply(InputWidgetManager, args);
            }

            var response = null;
            var overrideJqueryMethods = AbstractInputWidget.prototype.overrideJqueryMethods;
            this.each(function(i) {
                var result = null;
                var $input = $(this);
                var widget = InputWidgetManager.getWidget($input);

                if (!command) {
                    result = widget;
                } else if (!widget || !_.isFunction(widget[command])) {
                    if (_.indexOf(overrideJqueryMethods, command) !== -1) {
                        result = $input[command].apply($input, args);
                    } else if (widget) {
                        InputWidgetManager.error('Input widget doesn\'t support command "%s"', command);
                    }
                } else {
                    result = widget[command].apply(widget, args);
                }

                if (i === 0) {
                    response = result;
                }
            });

            return response;
        }
    });

    return InputWidgetManager;
});
