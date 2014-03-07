/*global define*/
define(['jquery', 'underscore', 'orotranslation/js/translator', './abstract-action'
    ], function ($, _, __, AbstractAction) {
    'use strict';

    /**
     * Allows to export grid data
     *
     * @export  orodatagrid/js/datagrid/action/export-action
     * @class   orodatagrid.datagrid.action.ExportAction
     * @extends orodatagrid.datagrid.action.AbstractAction
     */
    return AbstractAction.extend({

        /** @property oro.PageableCollection */
        collection: undefined,

        /** @property {oro.datagrid.ActionLauncher} */
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
