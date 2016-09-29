define(function(require) {
    'use strict';

    var TreeManageComponent;
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var routing = require('routing');
    var BasicTreeManageComponent = require('oroui/js/app/components/basic-tree-manage-component');

    /**
     * @export oronavigation/js/app/components/tree-manage-component
     * @extends oroui.app.components.BasicTreeManageComponent
     * @class oronavigation.app.components.TreeManageComponent
     */
    TreeManageComponent = BasicTreeManageComponent.extend({
        /**
         * @property {String}
         */
        menu: '',

        /**
         * @property {Number}
         */
        ownershipType: 0,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            TreeManageComponent.__super__.initialize.call(this, options);

            this.menu = options.menu;
            this.ownershipType = options.ownershipType;
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
            var route;
            var routeParams;
            if (this.onRootSelectRoute && selected.node.parent === '#') {
                route = this.onRootSelectRoute;
                routeParams = {menuName: this.menu};
            } else {
                route = this.onSelectRoute;
                routeParams = {menuName: this.menu, key: selected.node.id};
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

            var self = this;
            $.ajax({
                async: false,
                type: 'PUT',
                url: routing.generate(self.onMoveRoute, {ownershipType: this.ownershipType, menuName: this.menu}),
                data: {
                    key: data.node.id,
                    parentKey: data.parent,
                    position: data.position
                },
                success: function(result) {
                    if (!result.status) {
                        self.rollback(data);

                        var message = __('oro.ui.jstree.move_node_error', {nodeText: data.node.text});
                        if (result.message) {
                            message = result.message;
                        }
                        messenger.notificationFlashMessage('error', message);
                    } else if (self.reloadWidget) {
                        widgetManager.getWidgetInstanceByAlias(self.reloadWidget, function(widget) {
                            widget.render();
                        });
                    }
                }
            });
        }
    });

    return TreeManageComponent;
});
