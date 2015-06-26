/*jslint nomen:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    './abstract-action'
], function($, _, AbstractAction) {
    'use strict';

    var ExportAction;

    /**
     * Allows to export grid data
     *
     * @export  oro/datagrid/action/export-action
     * @class   oro.datagrid.action.ExportAction
     * @extends oro.datagrid.action.AbstractAction
     */
    ExportAction = AbstractAction.extend({

        /** @property oro.PageableCollection */
        collection: undefined,

        /**
         * {@inheritdoc}
         */
        initialize: function(options) {
            this.launcherOptions = {
                runAction: false
            };
            this.route = 'oro_datagrid_export_action';
            this.route_parameters = {
                gridName: options.datagrid.name
            };
            this.collection = options.datagrid.collection;

            ExportAction.__super__.initialize.apply(this, arguments);
        },

        /**
         * {@inheritdoc}
         */
        createLauncher: function(options) {
            var launcher = ExportAction.__super__.createLauncher.apply(this, arguments);
            // update 'href' attribute for each export type
            this.listenTo(launcher, 'expand', function(launcher) {
                var fetchData = this.collection.getFetchData();
                _.each(launcher.$el.find('.dropdown-menu a'), function(el) {
                    var $el = $(el);
                    $el.attr('href', this.getLink(_.extend({format: $el.data('key')}, fetchData)));
                }, this);
            });

            return launcher;
        }
    });

    return ExportAction;
});
