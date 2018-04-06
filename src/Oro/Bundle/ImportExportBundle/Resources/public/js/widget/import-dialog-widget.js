define(function(require) {
    'use strict';

    var ImportDialogWidget;
    var BaseDialogView = require('oro/dialog-widget');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var messenger = require('oroui/js/messenger');

    /**
     * @export  oroimportexport/js/app/widgets/import-dialog-widget
     * @class   oroimportexport.widget.ImportDialogWidget
     * @extends orowindows.widget.DialogWidget
     */
    ImportDialogWidget = BaseDialogView.extend({
        /**
         * @inheritDoc
         */
        constructor: function ImportDialogWidget() {
            ImportDialogWidget.__super__.constructor.apply(this, arguments);
        },

        _onContentLoad: function(content) {
            if (_.has(content, 'success')) {
                if (content.success) {
                    messenger.notificationFlashMessage('success', this.options.successMessage);
                } else {
                    messenger.notificationFlashMessage('error', this.options.errorMessage);
                }

                this.remove();
            } else {
                delete this.loading;
                this.disposePageComponents();
                this.setContent(content, true);
                this._triggerContentLoadEvents();
            }
        },

        _onContentLoadFail: function() {
            messenger.notificationFlashMessage('error', __('oro.importexport.import.fail.message'));
            this.remove();
        }
    });

    return ImportDialogWidget;
});
