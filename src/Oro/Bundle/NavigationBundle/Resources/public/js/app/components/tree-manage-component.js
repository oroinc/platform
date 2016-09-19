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
         * @param {Object} options
         */
        initialize: function(options) {
            TreeManageComponent.__super__.initialize.call(this, options);

            this.menu = options.menu;
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
                routeParams = {menu: this.menu};
            } else {
                route = this.onSelectRoute;
                routeParams = {menu: this.menu, key: selected.node.id};
            }
            mediator.execute('redirectTo', {url: routing.generate(route, routeParams)});
        },
    });

    return TreeManageComponent;
});
