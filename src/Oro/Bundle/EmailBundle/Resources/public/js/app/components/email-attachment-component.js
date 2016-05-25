define(function(require) {
    'use strict';

    var EmailAttachmentComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var EmailAttachmentSelectView = require('oroemail/js/app/views/email-attachment-select-view');
    var EmailAttachmentCollection = require('oroemail/js/app/models/email-attachment-collection');
    var EmailAttachmentCollectionView = require('oroemail/js/app/views/email-attachment-collection-view');

    /**
     * @exports EmailAttachmentComponent
     */
    EmailAttachmentComponent = BaseComponent.extend({
        /**
         * @type {EmailAttachmentCollection}
         */
        collection: null,

        /**
         * @type {EmailAttachmentCollectionView}
         */
        collectionView: null,

        /**
         * @type {EmailAttachmentSelectView}
         */
        popupView: null,

        /**
         * @type {EmailAttachmentCollection}
         */
        popupCollection: null,

        /**
         * @type {jQuery}
         */
        $uploadNewButton: null,

        /**
         * @type {jQuery}
         */
        $popupSelectButton: null,

        /**
         * @type {jQuery}
         */
        $popupContentEl: null,

        initialize: function(options) {
            this.popupCollection = new EmailAttachmentCollection(options.attachmentsAvailable || []);
            this.collection = new EmailAttachmentCollection(options.entityAttachments || []);
            this.collectionView = new EmailAttachmentCollectionView({
                collection: this.collection,
                el: options._sourceElement,
                listSelector: '#' + options.containerId,
                inputName: options.inputName,
                fileIcons: options.fileIcons
            });

            this.$uploadNewButton = this.findControlElement(options._sourceElement, options.uploadNewButton);
            this.$popupSelectButton = this.findControlElement(options._sourceElement, options.popupTriggerButton);
            this.$popupContentEl = this.findControlElement(options._sourceElement, options.popupContentEl);
            if (this.$popupSelectButton.length && this.$popupContentEl.length) {
                this.bindEvents();
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$uploadNewButton.off('.' + this.cid);
            this.$popupSelectButton.off('.' + this.cid);
            return EmailAttachmentComponent.__super__.dispose.call(this);
        },

        /**
         * Binds handlers to control elements
         */
        bindEvents: function() {
            var self = this;
            this.$popupSelectButton.on('click.' + this.cid, function() {
                var popupView = self.getPopupView();
                if (popupView.isShowed) {
                    popupView.hide();
                } else {
                    popupView.show();
                }
            });
            this.$uploadNewButton.on('click.' + this.cid, function() {
                self.collection.add({});
            });
        },

        /**
         *
         * @returns {EmailAttachmentSelectView}
         */
        getPopupView: function() {
            if (!this.popupView) {
                this.popupView = new EmailAttachmentSelectView({
                    el: this.$popupContentEl,
                    collection: this.popupCollection,
                    attachedCollection: this.collection
                });
                this.popupView.showHideFilter();
                this.popupView.showHideGroups();

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
        },

        /**
         * Looks for the control element relatively from the source element
         *
         * @param {jQuery} $sourceElement
         * @param {string} selector
         * @returns {jQuery}
         */
        findControlElement: function($sourceElement, selector) {
            return $sourceElement.parent().find(selector);
        }
    });

    return EmailAttachmentComponent;
});
