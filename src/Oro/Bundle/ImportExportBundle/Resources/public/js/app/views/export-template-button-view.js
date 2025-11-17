import BaseView from 'oroui/js/app/views/base/view';
import _ from 'underscore';
import $ from 'jquery';
import routing from 'routing';

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

export default ExportTemplateButtonView;
