/* global define */
define(['underscore', 'oro/translator', 'oro/datagrid/abstract-action', 'oro/export-handler'],
function(_, __, AbstractAction, exportHandler) {
    'use strict';

    /**
     * Allows to export grid data
     *
     * @export  oro/datagrid/export-action
     * @class   oro.datagrid.ExportAction
     * @extends oro.datagrid.AbstractAction
     */
    return AbstractAction.extend({

        /** @property oro.PageableCollection */
        collection: undefined,

        /** @property {String} */
        actionKey: '',

        /** @property {Object} */
        exportStartedNotification: null,

        /**
         * {@inheritdoc}
         */
        initialize: function(options) {
            this.launcherOptions = {
                links: [
                    {key: 'csv', label: 'CSV'}
                ]
            };
            this.frontend_handle = 'ajax';
            this.reloadData = false;
            this.route = 'oro_datagrid_extra_action';
            this.route_parameters = {
                gridName: options.datagrid.name,
                actionName: 'export'
            };
            this.collection = options.datagrid.collection;

            AbstractAction.prototype.initialize.apply(this, arguments);
        },

        /**
         * {@inheritdoc}
         */
        getActionParameters: function() {
            var result = _.extend({
                    format: this.actionKey
                },
                this.collection.getFetchData()
            );
            result[this.route_parameters.gridName + '[_pager][_disabled]'] = 1;
            return result;
        },

        /**
         * {@inheritdoc}
         */
        _doAjaxRequest: function () {
            this.exportStartedNotification = exportHandler.startExportNotificationMessage();
            AbstractAction.prototype._doAjaxRequest.apply(this, arguments);
        },

        /**
         * {@inheritdoc}
         */
        _onAjaxError: function(jqXHR, textStatus, errorThrown) {
            this.exportStartedNotification.close();
            this.exportStartedNotification = null;
            AbstractAction.prototype._onAjaxError.apply(this, arguments);
        },

        /**
         * {@inheritdoc}
         */
        _onAjaxSuccess: function(data, textStatus, jqXHR) {
            this.exportStartedNotification.close();
            this.exportStartedNotification = null;
            AbstractAction.prototype._onAjaxSuccess.apply(this, arguments);
        },

        /**
         * {@inheritdoc}
         */
        _showAjaxSuccessMessage: function(data) {
            exportHandler.handleExportResponse(data);
        }
    });
});
