define([
    'jquery',
    'underscore',
    './abstract-action',
    'orotranslation/js/translator',
    'oroui/js/mediator'
], function($, _, AbstractAction, __, mediator) {
    'use strict';

    /**
     * Allows to export grid data
     *
     * @export  oro/datagrid/action/export-action
     * @class   oro.datagrid.action.ExportAction
     * @extends oro.datagrid.action.AbstractAction
     */
    const ExportAction = AbstractAction.extend({

        /** @property oro.PageableCollection */
        collection: undefined,

        /** @property {Boolean} */
        isModalBinded: false,

        messages: {
            success: 'oro.datagrid.export.success.message',
            fail: 'oro.datagrid.export.fail.message'
        },

        /** @property {Object} */
        defaultMessages: {
            confirm_title: 'Export Confirmation',
            confirm_ok: 'Yes',
            confirm_cancel: 'Cancel'
        },

        /**
         * @inheritdoc
         */
        constructor: function ExportAction(options) {
            ExportAction.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.route = 'oro_datagrid_export_action';
            this.route_parameters = {
                gridName: options.datagrid.name
            };
            this.collection = options.datagrid.collection;
            this.reloadData = false;
            this.frontend_handle = 'ajax';

            ExportAction.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        createLauncher: function(options) {
            const launcher = ExportAction.__super__.createLauncher.call(this, options);
            // update 'href' attribute for each export type
            this.listenTo(launcher, 'expand', function(launcher) {
                const fetchData = this.collection.getFetchData();
                _.each(launcher.$el.find('.dropdown-menu a'), function(el) {
                    const $el = $(el);
                    if (!this.isModalBinded) {
                        this.createWarningModalForMaxRecords($el, launcher);
                    }
                    $el.attr('href', this.getLink(_.extend({format: $el.data('key')}, fetchData)));
                }, this);
                this.isModalBinded = true;
            });

            return launcher;
        },

        /**
         * @inheritdoc
         */
        getActionParameters: function() {
            return _.extend({format: this.actionKey}, this.collection.getFetchData());
        },

        /**
         * @inheritdoc
         */
        _onAjaxError: function(jqXHR) {
            mediator.execute('showFlashMessage', 'error', this.messages.fail);

            ExportAction.__super__._onAjaxError.call(this, jqXHR);
        },

        createWarningModalForMaxRecords: function($el, launcher) {
            const linkData = _.findWhere(launcher.links, {key: $el.data('key')});
            const state = this.collection.state || {};
            const totalRecords = state.totalRecords || 0;
            const self = this;

            if (linkData.show_max_export_records_dialog &&
                linkData.max_export_records &&
                totalRecords >= linkData.max_export_records) {
                $el.on('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    const link = $el;

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
                        const prevKey = self.actionKey;
                        self.actionKey = link.data('key');
                        self.execute();
                        self.actionKey = prevKey;
                        self.confirmModal.off();
                    });

                    self.confirmModal.open();
                });
            }
        }
    });

    return ExportAction;
});
