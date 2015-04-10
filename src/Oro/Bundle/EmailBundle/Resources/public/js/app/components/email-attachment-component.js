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
        popupCollection: null,

        initialize: function(options) {
            this.collection = new EmailAttachmentCollection();
            this.collectionView = new EmailAttachmentCollectionView({
                collection: this.collection,
                el: options._sourceElement,
                listSelector: '#' + options.container,
                inputName: options.inputName
            });

            if (options.popupTriggerButton && options.popupContentEl) {
                this.initPopup(options);
            }

            var models = options.entityAttachments == 'undefined' ? [] : options.entityAttachments;
            this.collection.add(models);
        },

        initPopup: function(options) {
            var self = this;

            var $dialogButton = $(options.popupTriggerButton);
            $dialogButton.click(function() {
                var popupView = self.getPopupView(options);
                if (popupView.isShowed) {
                    popupView.hide();
                } else {
                    popupView.show();
                }
            });
        },

        getPopupView: function(options) {
            if (!this.popupView) {
                this.popupCollection = new EmailAttachmentCollection();

                this.popupView = new EmailAttachmentSelectView({
                    popupTriggerButton: options.popupTriggerButton,
                    el: options.popupContentEl,
                    listSelector: options.popupAttachmentList,
                    collection: this.popupCollection
                });

                var models = typeof options.attachmentsAvailable == 'undefined' ? [] : options.attachmentsAvailable;
                this.popupCollection.add(models);

                var self = this;
                this.popupCollection.on('attach', function() {
                    self.popupCollection.each(function(model) {
                        if (model.get('checked')) {
                            var newModel = model.clone();
                            self.collection.add(newModel);
                        }
                    });

                    self.popupView.hide();
                });
            }

            return this.popupView;
        }
    });
});
