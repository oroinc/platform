define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const routing = require('routing');
    const DialogWidget = require('oro/dialog-widget');

    const ImportButtonsView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            alias: null,
            importRoute: 'oro_importexport_import_validate_export_template_form',
            dialogOptions: {
                title: __('oro.importexport.import.widget.title'),
                stateEnabled: false,
                incrementalPosition: false,
                dialogOptions: {
                    maxWidth: 650,
                    width: 650,
                    autoResize: true,
                    modal: true,
                    minHeight: 100,
                    dialogClass: 'import-dialog-widget'
                }
            },
            routeOptions: {}
        },

        routeOptions: {},

        $importButton: null,

        /**
         * @inheritdoc
         */
        constructor: function ImportButtonsView(options) {
            ImportButtonsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$importButton = this.$el;
            this.$importButton.on('click' + this.eventNamespace(), this.onImportClick.bind(this));

            this.routeOptions = {
                alias: this.options.alias,
                options: this.options.routeOptions
            };
        },

        onImportClick: function() {
            this._renderDialogWidget({
                url: routing.generate(this.options.importRoute, $.extend({}, this.routeOptions)),
                dialogOptions: {
                    title: this.options.importTitle
                }
            });
        },

        _renderDialogWidget: function(options) {
            const opts = $.extend(true, {}, this.options.dialogOptions, options);

            const widget = new DialogWidget(opts);

            widget.render();

            return widget;
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$importButton.off('click' + this.eventNamespace());
        }
    });

    return ImportButtonsView;
});
