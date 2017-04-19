define(function(require) {
    'use strict';

    var ActionManagerView;
    var $ = require('jquery');
    var _ = require('underscore');
    var ActionManager = require('oroui/js/jstree-action-manager');
    var BaseView = require('oroui/js/app/views/base/view');

    ActionManagerView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            actions: {}
        },

        /**
         * @property {Object}
         */
        elements: {
            wrapper: '[data-role="jstree-wrapper"]',
            container: '[data-role="jstree-container"]',
            actions: '[data-role="jstree-actions"]'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);
            this.options.$tree = this.$el.closest(this.elements.wrapper)
                .find(this.elements.container);

            ActionManagerView.__super__.initialize.apply(this, arguments);

            this.options.$tree.one('ready.jstree.actions', _.bind(this.collectActions, this));
        },

        collectActions: function() {
            _.each(ActionManager.getActions(this.options), function(action) {
                var options = _.extend({}, this.options.actions[action.name] || {}, {
                    $tree: this.options.$tree,
                    action: action.name
                });
                this.subview(action.name, new action.view(options));
            }, this);

            this.render();
        },

        render: function() {
            var $actions = this.$el.find(this.elements.actions);
            _.each(this.subviews, function(subview) {
                $actions.append(subview.render().$el);
            }, this);
            return this;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.options;
            delete this.elements;
            ActionManagerView.__super__.dispose.apply(this, arguments);
        }
    });

    return ActionManagerView;
});
