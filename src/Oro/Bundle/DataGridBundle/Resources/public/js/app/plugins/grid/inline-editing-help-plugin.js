define(function(require) {
    'use strict';

    var InlineEditingHelpPlugin;
    var __ = require('orotranslation/js/translator');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var $ = require('jquery');

    InlineEditingHelpPlugin = BasePlugin.extend({
        enable: function() {
            this.listenTo(this.main, 'holdInlineEditingBackdrop', this.onHoldInlineEditingBackdrop);
            this.listenTo(this.main, 'releaseInlineEditingBackdrop', this.onReleaseInlineEditingBackdrop);
            InlineEditingHelpPlugin.__super__.enable.call(this);
        },

        onHoldInlineEditingBackdrop: function() {
            $(document.body).append(
                '<div class="inline-editing-help-wrapper">' +
                    '<div class="inline-editing-help">' +
                        '<i class="icon-info"></i>' +
                    '</div>' +
                    '<div class="inline-editing-help-content popover bottom">' +
                        '<div class="arrow"></div>' +
                        __('oro.datagrid.inline_editing.help') +
                    '</div>' +
                '</div>'
            );
            $('.inline-editing-help-wrapper').click(function() {
                $('.inline-editing-help-content').toggle();
            });
        },

        onReleaseInlineEditingBackdrop: function() {
            $('body > .inline-editing-help-wrapper').remove();
        }
    });

    return InlineEditingHelpPlugin;
});
