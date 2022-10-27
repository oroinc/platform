define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');
    const $ = require('jquery');
    const routing = require('routing');

    const ExportTemplateButtonView = BaseView.extend({
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
         * @inheritdoc
         */
        constructor: function ExportTemplateButtonView(options) {
            ExportTemplateButtonView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$exportTemplateButton = this.$el;

            this.$exportTemplateButton.on('click' + this.eventNamespace(), this.onExportTemplateClick.bind(this));

            this.routeOptions = {
                options: this.options.routeOptions,
                exportTemplateJob: this.options.exportTemplateJob
            };
        },

        onExportTemplateClick: function() {
            const exportTemplateUrl = routing.generate(
                this.options.exportTemplateRoute,
                $.extend({}, this.routeOptions, {
                    processorAlias: this.options.exportTemplateProcessor
                })
            );

            window.open(exportTemplateUrl);
        },

        /**
         * @inheritdoc
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
