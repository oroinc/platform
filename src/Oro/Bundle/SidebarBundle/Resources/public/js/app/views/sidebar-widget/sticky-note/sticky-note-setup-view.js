import __ from 'orotranslation/js/translator';
import BaseWidgetSetupView from 'orosidebar/js/app/views/base-widget/base-widget-setup-view';
import template from 'tpl-loader!orosidebar/templates/sidebar-widget/sticky-note/sticky-note-setup-view.html';

const StickyNoteSetupView = BaseWidgetSetupView.extend({
    template,

    validation: {
        content: {
            NotBlank: {}
        }
    },

    /**
     * @inheritdoc
     */
    constructor: function StickyNoteSetupView(options) {
        StickyNoteSetupView.__super__.constructor.call(this, options);
    },

    widgetTitle: function() {
        return __('oro.sidebar.sticky_note_widget.settings');
    }
});

export default StickyNoteSetupView;
