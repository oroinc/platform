define(function(require, exports, module) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const ActionManager = require('oroui/js/jstree-action-manager');
    const BaseView = require('oroui/js/app/views/base/view');
    let config = require('module-config').default(module.id);
    config = _.extend({
        inlineActionsCount: null
    }, config);

    const ActionManagerView = BaseView.extend({
        /**
         * @property {Function}
         */
        template: require('tpl-loader!oroui/templates/jstree-actions-wrapper.html'),

        /**
         * @property {Function}
         */
        inlineTemplate: require('tpl-loader!oroui/templates/jstree-inline-actions-wrapper.html'),

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
         * @inheritdoc
         */
        constructor: function ActionManagerView(options) {
            ActionManagerView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);
            this.options.$tree = this.$el.closest(this.elements.wrapper)
                .find(this.elements.container);

            ActionManagerView.__super__.initialize.call(this, options);

            this.options.$tree.one('ready.jstree.actions', this.collectActions.bind(this));
        },

        collectActions: function() {
            _.each(ActionManager.getActions(this.options), function(action) {
                const options = _.extend({}, this.options.actions[action.name] || {}, {
                    $tree: this.options.$tree,
                    action: action.name
                });
                this.subview(action.name, new action.view(options));
            }, this);

            this.render();
        },

        render: function() {
            let template;
            if (this.options.inlineActionsCount && this.subviews.length <= this.options.inlineActionsCount) {
                template = 'inlineTemplate';
            }
            if (this.options.inlineActionsElement) {
                this.setElement($(this.options.inlineActionsElement));
            }

            this.$el.append(this.getTemplateFunction(template)(this.getTemplateData()));

            const $actions = this.$el.find(this.elements.actions);
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
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.options;
            delete this.elements;
            ActionManagerView.__super__.dispose.call(this);
        }
    });

    return ActionManagerView;
});
