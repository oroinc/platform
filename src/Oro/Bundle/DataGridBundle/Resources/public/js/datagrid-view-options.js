define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var Backbone = require('backbone');

    var DataGridViewOptions = {
        setDefaults: function(options) {
            return _.defaults(options, {
                tableView: true,
                rowTemplateSelector: null
            });
        },

        extend: function(view, viewOptions) {
            var extendedViewOptions = $.extend(true, view.prototype.viewOptions || {}, viewOptions);

            var ExtendedView = view.extend({
                tagName: extendedViewOptions.tableView === false ? 'div' : view.prototype.tagName,

                sourceViewOptions: viewOptions,

                viewOptions: extendedViewOptions,

                initialize: function(options) {
                    _.each(this.viewOptions.childViews, _.bind(function(view) {
                        if (options[view] && options[view].prototype instanceof Backbone.View) {
                            options[view] = DataGridViewOptions.extend(options[view], viewOptions);
                        }
                        if (this[view] && this[view].prototype instanceof Backbone.View) {
                            this[view] = DataGridViewOptions.extend(this[view], viewOptions);
                        }
                    }, this));

                    if (this.viewOptions.templateKey && this.viewOptions[this.viewOptions.templateKey]) {
                        this.template = _.template($(this.viewOptions[this.viewOptions.templateKey]).html());
                    }

                    return ExtendedView.__super__.initialize.apply(this, arguments);
                },

                render: function() {
                    if (this.viewOptions.className) {
                        this.$el.addClass(this.viewOptions.className);
                    }
                    if (this.viewOptions.templateKey && this.template) {
                        this.$el.html(this.template({
                            model: this.model ? this.model.attributes : {}
                        }));
                        return this;
                    }
                    return ExtendedView.__super__.render.apply(this, arguments);
                }
            });

            return ExtendedView;
        }
    };

    return DataGridViewOptions;
});
