define(function(require) {
    'use strict';

    var EmailAttachmentSelectView;
    var _ = require('underscore');
    var $ = require('jquery');
    var EmailAttachmentListRowView = require('oroemail/js/app/views/email-attachment-list-row-view');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    EmailAttachmentSelectView = BaseCollectionView.extend({
        itemView: EmailAttachmentListRowView,

        listSelector: '.attachment-list',

        fallbackSelector: '.no-items',

        isShowed: false,

        fileNameFilter: '',

        attachedCollection: null,

        events: {
            'click .cancel': 'cancelClick',
            'click .upload-new': 'uploadNewClick',
            'click .attach': 'attachClick',
            'input input.filter': 'filterChange'
        },

        /**
         * @inheritDoc
         */
        constructor: function EmailAttachmentSelectView() {
            EmailAttachmentSelectView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (!_.has(options, 'attachedCollection')) {
                throw new Error('Required option "attachedCollection" not found.');
            }

            EmailAttachmentSelectView.__super__.initialize.apply(this, arguments);
            this.attachedCollection = options.attachedCollection;
        },

        resolveListSelector: function(model) {
            if (model.get('type') === 1) {
                return '.entity-attachments-list';
            } else {
                return '.thread-attachments-list';
            }
        },

        insertView: function(model, view, position) {
            this.list = this.resolveListSelector(model);
            this.$list = $(this.list);
            arguments[2] = this.$list.get(0).children.length; // init position for each block separately
            EmailAttachmentSelectView.__super__.insertView.apply(this, arguments);
        },

        showHideGroups: function() {
            var $entityAttachments = this.$('.entity-attachments'); // 1
            var $threadAttachments = this.$('.thread-attachments'); // 2

            var entityCollection = this.collection.where({type: 1, visible: true});
            if (entityCollection.length > 0) {
                $entityAttachments.show();
            } else {
                $entityAttachments.hide();
            }

            var threadCollection = this.collection.where({type: 2, visible: true});
            if (threadCollection.length > 0) {
                $threadAttachments.show();
            } else {
                $threadAttachments.hide();
            }
        },

        showHideFilter: function() {
            var $filter = this.$('.filter-block');
            if (this.collection.length > 5) {
                $filter.show();
            } else {
                $filter.hide();
            }
        },

        cancelClick: function() {
            this.hide();
        },

        attachClick: function() {
            this.collection.trigger('attach');
        },

        uploadNewClick: function() {
            this.$('input.input-upload-new').click();
        },

        filterChange: function(event) {
            var value = $(event.target).val();

            this.collection.each(function(model) {
                if (model.get('fileName').indexOf(value) === 0) {
                    model.set('visible', true);
                } else {
                    model.set('visible', false);
                }
            });

            this.showHideGroups();
        },

        getTemplateFunction: function() {
            if (!this.template) {
                this.template = require('tpl!oroemail/templates/email-attachment/email-attachment-select-view.html');
            }

            return EmailAttachmentSelectView.__super__.getTemplateFunction.call(this);
        },

        show: function() {
            this.resetCheckedModels();
            this.$el.show();
            this.isShowed = true;
        },

        hide: function() {
            this.$el.hide();
            this.isShowed = false;
        },

        resetCheckedModels: function() {
            this.collection.each(function(model) {
                var newValue = Boolean(this.attachedCollection.get(model.get('id')));
                if (model.get('checked') !== newValue) {
                    model.set('checked', newValue);
                    this.renderItem(model);
                }
            }, this);
        }
    });

    return EmailAttachmentSelectView;
});
