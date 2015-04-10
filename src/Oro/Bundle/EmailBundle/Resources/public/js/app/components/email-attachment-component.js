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

            var models = options.items == 'undefined' ? [] : options.items;
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
                // todo change to real data
                this.popupCollection = new EmailAttachmentCollection([
                    {'id': 1, 'fileName': 'file1.jpg'},
                    {'id': 2, 'fileName': 'file2.jpg'},
                    {'id': 3, 'fileName': 'file3.jpg'},
                    {'id': 4, 'fileName': 'file4.jpg'}
                ]);

                this.popupView = new EmailAttachmentSelectView({
                    popupTriggerButton: options.popupTriggerButton,
                    el: options.popupContentEl,
                    listSelector: options.popupAttachmentList,
                    collection: this.popupCollection
                });

                var self = this;
                this.popupCollection.on('attach', function() {
                    self.popupCollection.each(function(model) {
                        if (model.get('checked')) {
                            model.set('attached', true);

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
