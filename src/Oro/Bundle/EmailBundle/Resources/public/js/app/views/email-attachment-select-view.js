/*global define*/
define(function (require) {
    'use strict';

    var EmailAttachmentSelectView,
        BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    EmailAttachmentSelectView = BaseCollectionView.extend({
        // popup will be attached related to this label
        label: null,
        isShowed: false,

        events: {
            'click .cancel':     'cancelClick',
            'click .upload-new': 'uploadNewClick',
            'click .attach':     'attachClick'
        },

        initialize: function(options) {
            EmailAttachmentSelectView.__super__.initialize.call(this, options);
            this.label = options.label;
        },

        render: function() {
            var templateFunc = this.getTemplateFunction();
            var html = templateFunc(this.getTemplateData());
            this.$el.html(html);
            this.$el.addClass('attachment-list-popup');

            var $label = $(this.label);
            var position= $label.position();

            this.$el.css('bottom', $label.height() * 2);
            this.$el.css('left', position.left);

            this.initSelect();

            $label.after(this.$el);

            return this;
        },

        initSelect: function() {
            this.$('select').multiselect({
                header: false,
                autoOpen: true
            });
        },

        cancelClick: function() {
            this.hide();
        },

        attachClick: function() {
            console.log('For implementation');
        },

        uploadNewClick: function() {
            console.log('For implementation');
        },

        getTemplateFunction: function() {
            if (!this.template) {
                this.template = $('#email-attachment-select-view').html();
            }

            return EmailAttachmentSelectView.__super__.getTemplateFunction.call(this);
        },

        show: function() {
            this.$el.show();
            this.isShowed = true;
        },

        hide: function() {
            this.$el.hide();
            this.isShowed = false;
        }
    });

    return EmailAttachmentSelectView;
});
