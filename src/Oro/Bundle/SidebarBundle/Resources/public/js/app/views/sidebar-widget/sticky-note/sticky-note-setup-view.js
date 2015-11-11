define(function(require) {
    'use strict';

    var StickyNoteSetupView;
    var __ = require('orotranslation/js/translator');
    var BaseWidgetSetupView = require('orosidebar/js/app/views/base-widget/base-widget-setup-view');

    StickyNoteSetupView = BaseWidgetSetupView.extend({
        template: require('tpl!orosidebar/templates/sidebar-widget/sticky-note/sticky-note-setup-view.html'),

        widgetTitle: function() {
            return __('oro.sidebar.sticky_note_widget.settings');
        },

        validation: {
            content: {
                NotBlank: {}
            }
        }
    });

    return StickyNoteSetupView;
});
