define(function(require) {
    'use strict';

    var EmailAttachmentCollectionView;
    var $ = require('jquery');
    var EmailAttachmentView = require('oroemail/js/app/views/email-attachment-view');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    /**
     * @exports EmailAttachmentCollectionView
     */
    EmailAttachmentCollectionView = BaseCollectionView.extend({
        itemView: EmailAttachmentView,

        listen: {
            'add collection': 'collectionAdd',
            'remove collection': 'collectionRemove'
        },

        initialize: function(options) {
            BaseCollectionView.__super__.initialize.apply(this, arguments);
            this.itemView = this.itemView.extend({
                inputName: options.inputName,
                fileIcons: options.fileIcons,
                collectionView: this
            });

            this.listSelector = options.listSelector;
            $(this.listSelector).css('padding-top', 5); // todo move to class styles
            $(this.listSelector).html('');

            this.$el.hide();
            this.collection.map(this.collectionAdd, this);
        },

        collectionAdd: function(model) {
            if (!model.get('id')) {
                this.getItemView(model).fileSelect();
            } else {
                this.showHideAttachmentRow();
            }
        },

        collectionRemove: function() {
            var self = this;
            this.collection.each(function(model) {
                if (model && !model.get('type') && !model.get('id')) {
                    self.collection.remove(model);
                }
            });
            this.showHideAttachmentRow();
        },

        showHideAttachmentRow: function() {
            if (this.collection.isEmpty()) {
                this.hide();
            } else {
                this.show();
            }
        },

        show: function() {
            this.$el.show();
        },

        hide: function() {
            this.$el.hide();
        }
    });

    return EmailAttachmentCollectionView;
});
