/*global define*/
define(['jquery', 'underscore', './abstract-action'
    ], function ($, _, AbstractAction) {
    'use strict';

    /**
     * Allows to export grid data
     *
     * @export  oro/datagrid/action/export-action
     * @class   oro.datagrid.action.ExportAction
     * @extends oro.datagrid.action.AbstractAction
     */
    return AbstractAction.extend({

        /** @property oro.PageableCollection */
        collection: undefined,

        /** @property {orodatagrid.datagrid.ActionLauncher} */
        launcher: null,

        /**
         * {@inheritdoc}
         */
        initialize: function (options) {
            this.launcherOptions = {
                runAction: false
            };
            this.route = 'oro_datagrid_export_action';
            this.route_parameters = {
                gridName: options.datagrid.name
            };
            this.collection = options.datagrid.collection;

            AbstractAction.prototype.initialize.apply(this, arguments);
        },

        /**
         * {@inheritdoc}
         */
        createLauncher: function (options) {
            this.launcher = AbstractAction.prototype.createLauncher.apply(this, arguments);
            // update 'href' attribute for each export type
            this.listenTo(this.launcher, 'expand', _.bind(function (launcher) {
                var fetchData = this.collection.getFetchData();
                _.each(launcher.$el.find('.dropdown-menu a'), function (el) {
                    var $el = $(el);
                    $el.attr('href', this.getLink(_.extend({format: $el.data('key')}, fetchData)));
                }, this);
            }, this));

            return this.launcher;
        }
    });
});
