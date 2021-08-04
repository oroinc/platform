define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const tools = require('oroui/js/tools');
    const routing = require('routing');
    const BaseTreeView = require('oroui/js/app/views/jstree/base-tree-view');

    const ConfigurationTreeView = BaseTreeView.extend({
        autoSelectFoundNode: true,

        /**
         * @inheritdoc
         */
        constructor: function ConfigurationTreeView(options) {
            ConfigurationTreeView.__super__.constructor.call(this, options);
        },

        isNodeHasHandler: function(node) {
            return node.children.length === 0;
        },

        redirect: function(node) {
            const parent = _.last(_.without(node.parents, '#'));
            const routeParams = _.extend({}, this.onSelectRouteParameters, {
                activeGroup: parent + '/' + node.id
            });

            const state = tools.unpackFromQueryString(location.search)[this.viewGroup] || {};
            if (_.isUndefined(routeParams[this.viewGroup])) {
                routeParams[this.viewGroup] = state;
            }
            const url = routing.generate(this.onSelectRoute, routeParams);
            // simulate click on real link to check page state
            const $link = $('<a>').attr('href', url);
            this.$tree.before($link);
            $link.click().remove();
        },

        onSelect: function(e, selected) {
            if (this.initialization) {
                return;
            } else if (!this.isNodeHasHandler(selected.node)) {
                return this._toggleParentNode(selected);
            }

            return this.redirect(selected.node);
        },

        _toggleParentNode: function(selected) {
            this.jsTreeInstance.toggle_node(selected.node);
            this.jsTreeInstance.deselect_node(selected.selected);
        }
    });

    return ConfigurationTreeView;
});
