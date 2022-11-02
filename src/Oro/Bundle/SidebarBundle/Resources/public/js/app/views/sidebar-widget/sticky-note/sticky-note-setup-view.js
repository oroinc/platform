define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const BaseWidgetSetupView = require('orosidebar/js/app/views/base-widget/base-widget-setup-view');

    const StickyNoteSetupView = BaseWidgetSetupView.extend({
        template: require('tpl-loader!orosidebar/templates/sidebar-widget/sticky-note/sticky-note-setup-view.html'),

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

    return StickyNoteSetupView;
});
