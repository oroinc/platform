define(function(require) {
    'use strict';

    const BaseDialogView = require('oro/dialog-widget');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const messenger = require('oroui/js/messenger');

    /**
     * @export  oroimportexport/js/app/widgets/import-dialog-widget
     * @class   oroimportexport.widget.ImportDialogWidget
     * @extends orowindows.widget.DialogWidget
     */
    const ImportDialogWidget = BaseDialogView.extend({
        /**
         * @inheritdoc
         */
        constructor: function ImportDialogWidget(options) {
            ImportDialogWidget.__super__.constructor.call(this, options);
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
