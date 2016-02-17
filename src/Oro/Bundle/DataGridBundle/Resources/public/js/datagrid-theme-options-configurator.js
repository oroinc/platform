define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');

    var DataGridThemeOptionsConfigurator = function(gridThemeOptions) {
        this.gridThemeOptions = gridThemeOptions || {};
        return _.bind(this.configure, this);
    };

    _.extend(DataGridThemeOptionsConfigurator.prototype, {
        defaults: {
            optionPrefix: '',
            tableView: true,
            className: '',
            hide: false,
            template: null,
            templateSelector: null,
            templateAsset: null
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

        tableViewOption: function(view, options, tableView) {
            if (tableView === false) {
                options.tagName = 'div';
            }
        },

        classNameOption: function(view, options, className) {
            if (!className) {
                return ;
            }

            if (options.className === undefined) {
                options.className = view.prototype.className;
            }

            if (!options.className) {
                options.className = className;
            } else if (_.isFunction(options.className)) {
                var oldClassName = options.className;
                options.className = function() {
                    var oldClassNameValue = oldClassName.call(this);
                    if (oldClassNameValue) {
                        return oldClassNameValue + ' ' + className;
                    }
                    return className;
                };
            } else {
                options.className += ' ' + className;
            }
        },

        templateSelectorOption: function(view, options, template) {
            if (template) {
                options.template = _.template($(template).html());
            }
        },

        templateAssetOption: function(view, options, template) {
            if (template) {
                options.template = require('tpl!' + template);
            }
        },

        templateOption: function(view, options, template) {
            if (template) {
                options.template = _.template(template);
            }
        }
    });

    return DataGridThemeOptionsConfigurator;
});
