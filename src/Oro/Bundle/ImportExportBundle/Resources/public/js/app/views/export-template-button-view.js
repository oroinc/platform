define(function(require) {
    'use strict';

    var ExportTemplateButtonView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');

    ExportTemplateButtonView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            exportTemplateRoute: 'oro_importexport_export_template',
            exportTemplateProcessor: null,
            exportTemplateJob: null,
            routeOptions: {}
        },

        $exportTemplateButton: null,

        /**
         * @inheritDoc
         */
        constructor: function ExportTemplateButtonView() {
            ExportTemplateButtonView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$exportTemplateButton = this.$el;

            this.$exportTemplateButton.on('click' + this.eventNamespace(), _.bind(this.onExportTemplateClick, this));

            this.routeOptions = {
                options: this.options.routeOptions,
                exportTemplateJob: this.options.exportTemplateJob
            };
        },

        onExportTemplateClick: function() {
            var exportTemplateUrl = routing.generate(
                this.options.exportTemplateRoute,
                $.extend({}, this.routeOptions, {
                    processorAlias: this.options.exportTemplateProcessor
                })
            );

            window.open(exportTemplateUrl);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$exportTemplateButton.off('click' + this.eventNamespace());
        }
    });

    return ExportTemplateButtonView;
});
