define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const widgetManager = require('oroui/js/widget-manager');
    const messenger = require('oroui/js/messenger');
    const routing = require('routing');
    const BaseTreeManageView = require('oroui/js/app/views/jstree/base-tree-manage-view');

    const TreeManageView = BaseTreeManageView.extend({
        /**
         * @property {String}
         */
        menu: '',

        /**
         * @property {String}
         */
        successMessage: '',

        /**
         * @property {String}
         */
        errorMessage: 'oro.ui.jstree.move_node_error',

        /**
         * @property {Object}
         */
        context: {},

        /**
         * @inheritdoc
         */
        constructor: function TreeManageView(options) {
            TreeManageView.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            TreeManageView.__super__.initialize.call(this, options);
            this.menu = options.menu;
            this.successMessage = options.successMessage || 'oro.navigation.menuupdate.moved_success_message';
            this.context = options.context;
        },

        customizeTreeConfig(options, config) {
            config = TreeManageView.__super__.customizeTreeConfig.call(this, options, config);

            config.plugins = [
                ...config.plugins,
                'types'
            ];

            config.types = {
                '#': {
                    valid_children: ['menu_item']
                },
                'default': {}
            };

            if (options.maxDepth) {
                config.types.default.max_depth = options.maxDepth;
            }

            return config;
        },

        /**
         * Triggers after node selection in tree
         *
         * @param {Event} e
         * @param {Object} selected
         */
        onSelect: function(e, selected) {
            if (this.initialization || !this.updateAllowed) {
                return;
            }
            let route;
            let routeParams;
            if (this.onRootSelectRoute && selected.node.parent === '#') {
                route = this.onRootSelectRoute;
                routeParams = {menuName: this.menu, context: this.context};
            } else {
                route = this.onSelectRoute;
                routeParams = {
                    context: this.context,
                    menuName: this.menu,
                    key: selected.node.id
                };
            }
            mediator.execute('redirectTo', {url: routing.generate(route, routeParams)});
        },

        /**
         * Triggers after node move
         *
         * @param {Object} e
         * @param {Object} data
         */
        onMove: function(e, data) {
            if (this.moveTriggered) {
                return;
            }

            const self = this;
            $.ajax({
                async: false,
                type: 'PUT',
                url: routing.generate(self.onMoveRoute, {
                    context: this.context,
                    menuName: this.menu
                }),
                data: {
                    key: data.node.id,
                    parentKey: data.parent,
                    position: data.position
                },
                success: function() {
                    if (self.reloadWidget) {
                        widgetManager.getWidgetInstanceByAlias(self.reloadWidget, function(widget) {
                            widget.render();
                        });
                    }
                    const reloadMessage = _.__('oro.navigation.menuupdate.reload_link.label');
                    const reloadLink = '<a href="#" onclick="window.location.reload(false);return false;">' +
                        reloadMessage + '</a>';

                    const message = _.__(self.successMessage, {
                        nodeText: data.node.text,
                        reload_link: reloadLink
                    });
                    messenger.notificationFlashMessage('success', message);
                },
                errorHandlerMessage: false,
                error: function(xhr) {
                    self.rollback(data);
                    let message = _.__(self.errorMessage, {nodeText: data.node.text});
                    if (xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    messenger.notificationFlashMessage('error', message);
                }
            });
        }
    });

    return TreeManageView;
});
