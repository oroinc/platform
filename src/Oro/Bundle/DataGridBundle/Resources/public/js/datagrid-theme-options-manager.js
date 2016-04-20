define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var DataGridThemeOptionsManager;

    DataGridThemeOptionsManager = {
        defaults: {
            optionPrefix: '',
            tagName: '',
            className: '',
            hide: false,
            template: null,
            templateSelector: null
        },

        createConfigurator: function(gridThemeOptions) {
            var configurator = _.extend({
                gridThemeOptions: gridThemeOptions
            }, this);
            return _.bind(configurator.configure, configurator);
        },

        configure: function(view, options) {
            var themeOptions = options.themeOptions = $.extend(
                true,
                {},
                this.defaults,
                view.prototype.themeOptions || {},
                options.themeOptions || {},
                this.gridThemeOptions
            );

            var optionPrefix = themeOptions.optionPrefix;
            if (optionPrefix) {
                _.each(themeOptions, function(value, option) {
                    if (optionPrefix && option.indexOf(optionPrefix) === 0) {
                        delete themeOptions[option];
                        option = option.replace(optionPrefix, '');
                        option = option.charAt(0).toLowerCase() + option.slice(1);//transform: SomeOption > someOption
                        themeOptions[option] = value;
                    }
                });
            }

            _.each(themeOptions, _.bind(function(value, option) {
                var configurator = option + 'Option';
                if (_.isFunction(this[configurator])) {
                    this[configurator](view, options, value);
                }
            }, this));
        },

        mergeOption: function(view, options, key, value, mergeCallback) {
            if (!value) {
                return;
            }

            if (options[key] === undefined) {
                options[key] = view.prototype[key];
            }

            if (_.isFunction(options[key])) {
                var oldValueFunction = options[key];
                options[key] = function() {
                    var oldValue = oldValueFunction.call(this);
                    return mergeCallback(oldValue, value);
                };
            } else {
                options[key] = mergeCallback(options[key], value);
            }
        },

        tagNameOption: function(view, options, tagName) {
            if (tagName) {
                options.tagName = tagName;
            }
        },

        attributesOption: function(view, options, attributes) {
            this.mergeOption(view, options, 'attributes', attributes, function(option, themeOption) {
                return $.extend(true, {}, option || {}, themeOption);
            });
        },

        classNameOption: function(view, options, className) {
            this.mergeOption(view, options, 'className', className, function(option, themeOption) {
                return (option ? option + ' ' : '') + themeOption;
            });
        },

        templateOption: function(view, options, template) {
            if (template) {
                options.template = _.template(template);
            }
        },

        templateSelectorOption: function(view, options, template) {
            if (template) {
                options.template = _.template($(template).html());
            }
        }
    };

    return DataGridThemeOptionsManager;
});
