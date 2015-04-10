/*global define*/
define(function (require) {
    'use strict';

    var EmailAttachmentCollectionView,
        $ = require('jquery'),
        EmailAttachmentView = require('oroemail/js/app/views/email-attachment-view'),
        BaseCollectionView = require('oroui/js/app/views/base/collection-view');

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
            BaseCollectionView.__super__.initialize.apply(this, options);
            this.itemView = this.itemView.extend({
                inputName: options.inputName
                /*collectionView: this*/
            });

            this.listSelector = options.listSelector;
            $(this.listSelector).css('padding-top', 5); // todo move to class styles
            $(this.listSelector).html('');

            this.$el.hide();
        },

        collectionAdd: function(model) {
            this.showHideAttachmentRow();
        },

        collectionRemove: function() {
            this.showHideAttachmentRow();
        },

        showHideAttachmentRow: function() {
            if (this.collection.isEmpty()) {
                this.$el.hide();
            } else {
                this.$el.show();
            }
        }
    });

    return EmailAttachmentCollectionView;
});
