define(function(require) {
    'use strict';

    var ActionManagerView;
    var $ = require('jquery');
    var _ = require('underscore');
    var ActionManager = require('oroui/js/jstree-action-manager');
    var BaseView = require('oroui/js/app/views/base/view');
    var config = require('module').config();
    config = _.extend({
        inlineActionsCount: null
    }, config);

    ActionManagerView = BaseView.extend({
        /**
         * @property {Function}
         */
        template: require('tpl!oroui/templates/jstree-actions-wrapper.html'),

        /**
         * @property {Function}
         */
        inlineTemplate: require('tpl!oroui/templates/jstree-inline-actions-wrapper.html'),

        /**
         * @property {Object}
         */
        options: {
            actions: {},
            inlineActionsCount: config.inlineActionsCount,
            inlineActionsElement: null
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
        constructor: function ActionManagerView() {
            ActionManagerView.__super__.constructor.apply(this, arguments);
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
            var template;
            if (this.options.inlineActionsCount && this.subviews.length <= this.options.inlineActionsCount) {
                template = 'inlineTemplate';
            }
            if (this.options.inlineActionsElement) {
                this.setElement($(this.options.inlineActionsElement));
            }

            this.$el.append(this.getTemplateFunction(template)(this.getTemplateData()));

            var $actions = this.$el.find(this.elements.actions);
            _.each(this.subviews, function(subview) {
                $actions.append(subview.render().$el);
            }, this);

            return this;
        },

        getTemplateData: function() {
            return _.extend({}, this.options, {
                subviewsCount: this.subviews.length
            });
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
