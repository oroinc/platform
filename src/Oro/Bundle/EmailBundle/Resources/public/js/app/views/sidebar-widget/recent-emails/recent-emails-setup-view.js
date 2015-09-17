define(function(require) {
    'use strict';

    var RecentEmailsContentView;
    var __ = require('orotranslation/js/translator');
    var BaseWidgetSetupView = require('orosidebar/js/app/views/base-widget/base-widget-setup-view');

    RecentEmailsContentView = BaseWidgetSetupView.extend({
        template: require('tpl!oroemail/templates/sidebar-widget/recent-emails/recent-emails-setup-view.html'),

        widgetTitle: function() {
            return __('oro.email.recent_emails_widget.settings');
        },

        validation: {
            limit: {
                NotBlank: {},
                Regex: {pattern: '/^\\d+$/'},
                Number: {min: 1, max: 20}
            }
        },

        render: function() {
            RecentEmailsContentView.__super__.render.apply(this, arguments);
            this.initLayout();
        },

        fetchFromData: function() {
            var data = RecentEmailsContentView.__super__.fetchFromData.call(this);
            data.limit = Number(data.limit);
            return data;
        },

        onSubmit: function() {
            var folderId;
            var title;
            RecentEmailsContentView.__super__.onSubmit.apply(this, arguments);
            folderId = this.model.get('settings').folderId;
            if (folderId) {
                title = this.$el.find('.select2[name=folderId] option[value=' + folderId + ']').text();
            } else {
                title = __('oro.email.recent_emails_widget.title_all_folders');
            }
            this.model.set('title', title);
        }
    });

    return RecentEmailsContentView;
});
