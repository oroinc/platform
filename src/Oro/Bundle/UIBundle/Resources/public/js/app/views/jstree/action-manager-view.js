define(function(require) {
    'use strict';

    var ActionManagerView;
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var BaseView = require('oroui/js/app/views/base/view');

    ActionManagerView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {},

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

            this._loadModules();
        },

        /**
         * Loads modules for each action
         * */
        _loadModules: function() {
            var modules = {};
            _.each(this.options.actions, function(action, key) {
                modules[key] = action.view;
            });
            var self = this;
            tools.loadModules(modules, function(modules) {
                _.each(modules, function(View, key) {
                    var options = _.extend({
                        $tree: self.options.$tree,
                        action: key
                    }, _.omit(self.options.actions[key], 'view'));
                    self.subview(key, new View(options));
                });
                self.render();
            });
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
