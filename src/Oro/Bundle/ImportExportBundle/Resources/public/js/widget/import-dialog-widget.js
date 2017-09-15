define(function(require) {
    'use strict';

    var ImportDialogWidget;
    var BaseDialogView = require('oro/dialog-widget');
    var __ = require('orotranslation/js/translator');
    var messenger = require('oroui/js/messenger');

    /**
     * @export  oroimportexport/js/app/widgets/import-dialog-widget
     * @class   oroimportexport.widget.ImportDialogWidget
     * @extends orowindows.widget.DialogWidget
     */
    ImportDialogWidget = BaseDialogView.extend({
        _onContentLoadFail: function() {
            messenger.notificationFlashMessage('error', __('oro.importexport.import.fail.message'));
            this.remove();
        }
    });

    return ImportDialogWidget;
});
