import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orosidebar/templates/sidebar-widget-container/widget-container-icon.html';

const WidgetContainerIconView = BaseView.extend({
    template,

    listen: {
        'change model': 'render'
    },

    /**
     * @inheritdoc
     */
    constructor: function WidgetContainerIconView(options) {
        WidgetContainerIconView.__super__.constructor.call(this, options);
    }
});

export default WidgetContainerIconView;
