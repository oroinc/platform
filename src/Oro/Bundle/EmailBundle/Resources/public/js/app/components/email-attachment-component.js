define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const EmailAttachmentSelectView = require('oroemail/js/app/views/email-attachment-select-view');
    const EmailAttachmentCollection = require('oroemail/js/app/models/email-attachment-collection');
    const EmailAttachmentCollectionView = require('oroemail/js/app/views/email-attachment-collection-view');

    /**
     * @exports EmailAttachmentComponent
     */
    const EmailAttachmentComponent = BaseComponent.extend({
        options: _.extend({}, BaseComponent.prototype.options, {
            popupTriggerButton: '[data-role="attachment-choose"]',
            uploadNewButton: '[data-role="attachment-upload"]',
            popupContentEl: '[data-role="attachment-popup"]',
            containerId: null,
            inputName: null,
            fileIcons: [],
            attachmentsAvailable: [],
            entityAttachments: []
        }),

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

        /**
         * @inheritdoc
         */
        constructor: function EmailAttachmentComponent(options) {
            EmailAttachmentComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$el = options._sourceElement;

            this.popupCollection = new EmailAttachmentCollection(this.options.attachmentsAvailable || []);
            this.collection = new EmailAttachmentCollection(
                _.map(
                    this.options.entityAttachments || [],
                    (value, index) => {
                        return {index, ...value};
                    }
                )
            );
            this.collectionView = new EmailAttachmentCollectionView({
                collection: this.collection,
                el: this.$el,
                listSelector: '#' + this.options.containerId,
                inputName: this.options.inputName,
                fileIcons: this.options.fileIcons
            });

            this.$uploadNewButton = this.findControlElement(this.$el, this.options.uploadNewButton);
            this.$popupSelectButton = this.findControlElement(this.$el, this.options.popupTriggerButton);
            this.$popupContentEl = this.findControlElement(this.$el, this.options.popupContentEl);
            if (this.$popupSelectButton.length && this.$popupContentEl.length) {
                this.bindEvents();
            }
            this.$form = this.$el.closest('form');
            this.$form.on('email-template:loaded.' + this.cid, (event, emailData) => {
                this.collection.set(
                    _.map(
                        emailData.attachments || [],
                        (value, index) => {
                            return {index, ...value};
                        }
                    )
                );
            });
            this.applyAttachmentValidation();
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$uploadNewButton.off('.' + this.cid);
            this.$popupSelectButton.off('.' + this.cid);
            this.$form.off('.' + this.cid);
            return EmailAttachmentComponent.__super__.dispose.call(this);
        },

        applyAttachmentValidation: function() {
            const self = this;
            this.$form.on('submit.' + this.cid, function(event) {
                if (self.$form.find('.attachment-item__errors').length > 0) {
                    event.preventDefault();
                    return false;
                }
            });
        },

        /**
         * Binds handlers to control elements
         */
        bindEvents: function() {
            const self = this;
            this.$popupSelectButton.on('click.' + this.cid, function() {
                const popupView = self.getPopupView();
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

                const self = this;
                this.popupCollection.on('attach', function() {
                    self.popupCollection.each(function(model) {
                        if (model.get('checked')) {
                            const newModel = model.clone();
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
