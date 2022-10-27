import BaseView from 'oroui/js/app/views/base/view';
import mediator from 'oroui/js/mediator';
import __ from 'orotranslation/js/translator';

const WysiwygContentPreview = BaseView.extend({
    /**
     * @inheritdoc
     */
    events: {
        'click a': 'onClick'
    },

    /**
     * @inheritdoc
     */
    constructor: function WysiwygContentPreview(options) {
        WysiwygContentPreview.__super__.constructor.call(this, options);
    },

    /**
     * @param {Object} e
     */
    onClick(e) {
        if (e.target.getAttribute('href') === null) {
            return;
        }

        e.preventDefault();

        mediator.execute(
            'showFlashMessage',
            'warning',
            __('oro.entity_config.content_preview.message_prevent_click')
        );
    }
});

export default WysiwygContentPreview;
