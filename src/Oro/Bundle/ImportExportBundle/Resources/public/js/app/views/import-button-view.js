define(function(require) {
    'use strict';

    var ImportButtonsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var routing = require('routing');
    var DialogWidget = require('oro/dialog-widget');

    ImportButtonsView = BaseView.extend({
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
         * @inheritDoc
         */
        constructor: function ImportButtonsView() {
            ImportButtonsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$importButton = this.$el;
            this.$importButton.on('click' + this.eventNamespace(), _.bind(this.onImportClick, this));

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
            var opts = $.extend(true, {}, this.options.dialogOptions, options);

            var widget = new DialogWidget(opts);

            widget.render();

            return widget;
        },

        /**
         * @inheritDoc
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
