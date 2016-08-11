define([
    'jquery',
    'underscore',
    './abstract-action',
    'orotranslation/js/translator'
], function($, _, AbstractAction, __) {
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

        /** @property {Boolean} */
        isModalBinded: false,

        /** @property {Object} */
        defaultMessages: {
            confirm_title: 'Export Confirmation',
            confirm_ok: 'Yes',
            confirm_cancel: 'Cancel'
        },

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
                    if (!this.isModalBinded) {
                        this.createWarningModalForMaxRecords($el, launcher);
                    }
                    $el.attr('href', this.getLink(_.extend({format: $el.data('key')}, fetchData)));
                }, this);
                this.isModalBinded = true;
            });

            return launcher;
        },

        createWarningModalForMaxRecords: function($el, launcher) {
            var linkData = _.findWhere(launcher.links, {key: $el.data('key')});
            var state = this.collection.state || {};
            var totalRecords = state.totalRecords || 0;
            var self = this;

            if (linkData.show_max_export_records_dialog &&
                linkData.max_export_records &&
                totalRecords >= linkData.max_export_records) {
                $el.on('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    var link = $el;

                    self.confirmModal = (new self.confirmModalConstructor({
                        title: __(self.messages.confirm_title),
                        content: __(
                            'oro.datagrid.export.max_limit_message',
                            {max_limit: linkData.max_export_records, total: totalRecords}
                            ),
                        okText: __(self.messages.confirm_ok),
                        cancelText: __(self.messages.confirm_cancel),
                        allowOk: self.allowOk
                    }));

                    self.confirmModal.on('ok', function() {
                        window.location.href = link.attr('href');
                        self.confirmModal.off();
                    });

                    self.confirmModal.open();
                });
            }
        }
    });

    return ExportAction;
});
