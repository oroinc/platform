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

        widgets: {},

        _cachedWidgetsByPriority: null,

        /**
         * @param {String} key
         * @param {Object} widget
         */
        addWidget: function(key, widget) {
            _.defaults(widget, {
                key: key,
                priority: 10,
                selector: '',
                Widget: null
            });

            this.widgets[widget.key] = widget;
            delete this._cachedWidgetsByPriority;
            delete this._cachedCompoundQuery;
        },

        /**
         * @param {String} key
         */
        removeWidget: function(key) {
            delete this.widgets[key];
            delete this._cachedWidgetsByPriority;
            delete this._cachedCompoundQuery;
        },

        getWidgetsByPriority: function() {
            if (!this._cachedWidgetsByPriority) {
                var self = this;
                this._cachedWidgetsByPriority = [];
                _.each(_.sortBy(self.widgets, 'priority'), function(widget) {
                    if (!self.isValidWidget(widget)) {
                        self.error('Input widget "%s" is invalid', widget.key);
                        return;
                    }
                    self._cachedWidgetsByPriority.push(widget);
                });
            }
            return this._cachedWidgetsByPriority;
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
         */
        create: function($inputs) {
            var self = this;
            var widgetsByPriority = this.getWidgetsByPriority();

            _.each($inputs, function(input) {
                var $input = $(input);
                if (self.hasWidget($input)) {
                    return ;
                }

                for (var i = 0; i < widgetsByPriority.length; i++) {
                    var widget = widgetsByPriority[i];
                    if (self.isApplicable($input, widget)) {
                        self.createWidget($input, widget.Widget, {});
                        break;
                    }
                }
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
         * @param {AbstractInputWidget|Function} Widget
         * @param {Object} options
         * @param {String} humanName - widget key (human name) assigned to this widget
         * @returns {AbstractInputWidget|Object}
         */
        createWidget: function($input, Widget, options, humanName) {
            if (!options) {
                options = {};
            }
            options.$el = $input;
            var widget = new Widget(options);
            if (!widget.isInitialized()) {
                widget.dispose();
            } else {
                $input.attr('data-bound-input-widget', humanName || 'no-name');
            }
        },

        /**
         * @param {jQuery} $input
         * @returns {boolean}
         */
        hasWidget: function($input) {
            return Boolean($input.attr('data-bound-input-widget'));
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
        },

        getCompoundQuery: function() {
            if (!this._cachedCompoundQuery) {
                var queries = [];
                var widgetsByPriority = this.getWidgetsByPriority();
                for (var i = 0; i < widgetsByPriority.length; i++) {
                    var widget = widgetsByPriority[i];
                    queries.push(widget.selector);
                }
                this._cachedCompoundQuery = queries.join(',');
            }
            return this._cachedCompoundQuery;
        },

        /**
         * Finds and initializes all input widgets in container
         */
        seekAndCreateWidgetsInContainer: function($container) {
            var foundElements = $container.find(this.getCompoundQuery()).filter(
                ':not(' +
                (this.noWidgetSelector ? (this.noWidgetSelector + ',') : '') +
                '[data-bound-input-widget], [data-page-component-module], [data-bound-component]' +
                ')'
            );
            this.create(foundElements);
            $container.data('attachedWidgetsCount',
                ($container.data('attachedWidgetsCount') || 0) + foundElements.length);
        },

        /**
         * Finds and destroys all input widgets in container
         */
        seekAndDestroyWidgetsInContainer: function($container) {
            if (!$container.data('attachedWidgetsCount')) {
                // no inputWidgets
                return;
            }
            var self = this;
            $container.find('[data-bound-input-widget]').each(function() {
                self.getWidget($(this)).dispose();
            });
            $container.data('attachedWidgetsCount', 0);
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
         *     $(':input').inputWidget('create'); //create widgets
         *     $('#container').inputWidget('seekAndCreate'); //create widgets in container
         *     $('#container').inputWidget('seekAndDestroy'); //destroys widgets in container
         *     $(':input').inputWidget('refresh'); //update widget, for example after input value change
         *     $(':input:first').inputWidget('getContainer'); //get widget root element
         *     $(':input').inputWidget('setWidth', 100); //set widget width
         *     $(':input').inputWidget('dispose'); //destroy widgets and dispose widget instance
         *
         * @param {String|null} command
         * @returns {mixed}
         */
        inputWidget: function(command) {
            if (command === 'create') {
                return InputWidgetManager.create(this);
            }
            if (command === 'seekAndCreate') {
                return InputWidgetManager.seekAndCreateWidgetsInContainer(this);
            }
            if (command === 'seekAndDestroy') {
                return InputWidgetManager.seekAndDestroyWidgetsInContainer(this);
            }

            var response = null;
            var args = Array.prototype.slice.call(arguments, 1);
            this.each(function(i) {
                var result = null;
                var $input = $(this);
                var widget = InputWidgetManager.getWidget($input);

                if (!command) {
                    result = widget;
                } else if (widget) {
                    if (!_.isFunction(widget[command])) {
                        InputWidgetManager.error('Input widget doesn\'t support command "%s"', command);
                    } else {
                        result = widget[command].apply(widget, args);
                    }
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
