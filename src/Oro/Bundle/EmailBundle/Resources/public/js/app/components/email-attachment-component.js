/*global define*/
define(function (require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery'),
        EmailAttachmentSelectView = require('oroemail/js/app/views/email-attachment-select-view'),
        EmailAttachmentCollection = require('oroemail/js/app/models/email-attachment-collection'),
        EmailAttachmentCollectionView = require('oroemail/js/app/views/email-attachment-collection-view');

    /**
     * @exports EmailAttachmentComponent
     */
    return BaseComponent.extend({
        collection: null,
        collectionView: null,
        popupView: null,

        initialize: function(options) {
            this.collection = new EmailAttachmentCollection();
            this.collectionView = new EmailAttachmentCollectionView({
                collection: this.collection,
                el: options._sourceElement,
                listSelector: '#' + options.container,
                inputName: options.inputName
            });

            if (options.dialogButton) {
                this.initDialogButton(options.dialogButton);
            }

            var models = options.items == 'undefined' ? [] : options.items;
            this.collection.add(models);
        },

        initDialogButton: function(dialogButton) {
            var self = this;

            var $dialogButton = $('#' + dialogButton);
            $dialogButton.click(function() {
                var popupView = self.getPopupView(this);
                if (popupView.isShowed) {
                    popupView.hide();
                } else {
                    popupView.show();
                }
            });
        },

        getPopupView: function(label) {
            if (!this.popupView) {
                this.popupView = new EmailAttachmentSelectView({
                    label: label,
                    collection: new EmailAttachmentCollection()
                });
            }

            return this.popupView;
        }
    });
});
