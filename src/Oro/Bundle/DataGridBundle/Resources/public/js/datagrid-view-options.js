define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');

    var DataGridViewOptions = {
        setDefaults: function(options) {
            return _.defaults(options, {
                tableView: true,
                rowTemplateSelector: null,
                childViews: []
            });
        },

        setViewOptions: function(view, options) {
            var viewOptions = view.viewOptions = $.extend(true, view.viewOptions || {}, options);

            if (!viewOptions.tableView && view.tagName !== 'div') {
                var $oldEl = view.$el;
                view.tagName = 'div';
                view.setElement($('<div/>'), true);
                view.$el.addClass($oldEl.attr('class'));
                $oldEl[0] = view.$el[0];
            }

            if (!view.template && viewOptions.templateKey && viewOptions[viewOptions.templateKey]) {
                view.template = _.template($(viewOptions[viewOptions.templateKey]).html());
                view.render = function() {
                    view.$el.html(view.template(view.model.attributes));
                    return this;
                };
            }

            _.each(viewOptions.childViews, function(view) {
                DataGridViewOptions.setViewOptions(view, options);
            });
        }
    };

    return DataGridViewOptions;
});
