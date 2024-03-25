import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!oroform/templates/expression-editor-extensions/button.html';

const SidePanelButton = BaseView.extend({
    /**
     * @inheritdoc
     */
    template: template,

    /**
     * @inheritdoc
     */
    events: {
        click: 'onClick'
    },

    /**
     * @inheritdoc
     */
    noWrap: true,

    /**
     * @inheritdoc
     */
    constructor: function SidePanelButton(options) {
        SidePanelButton.__super__.constructor.call(this, options);
    },

    onClick(e) {
        const handler = this.model.get('handler');

        if (typeof handler === 'function') {
            handler(e);
        }
    }
});

export default SidePanelButton;
