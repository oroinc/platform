define(function(require) {
    'use strict';

    var RecentEmailsContentView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var BaseWidgetSetupView = require('orosidebar/js/app/views/base-widget/base-widget-setup-view');

    RecentEmailsContentView = BaseWidgetSetupView.extend({
        template: require('tpl!oroemail/templates/sidebar-widget/recent-emails/recent-emails-setup-view.html'),

        foldersData: [],

        initialize: function() {
            RecentEmailsContentView.__super__.initialize.apply(this, arguments);
            $.getJSON(routing.generate('oro_api_get_emailorigins'),
                _.bind(function(data) {
                    this.foldersData = this.parseFoldersData(data);
                    this.render();
                }, this)
            );
        },

        parseFoldersData: function(data) {
            var mailboxes = [];
            _.each(data, function(mailbox) {
                var text = mailbox.properties.user;
                if (text) {
                    mailboxes.push({
                        text: text,
                        children: mailbox.folders.map(function(folder) {
                            return {
                                id: folder.id,
                                text: folder.fullName
                            };
                        })
                    });
                }
            });
            return mailboxes;
        },
        getTemplateData: function() {
            var data = this.model.toJSON();
            data.foldersData = JSON.stringify(this.foldersData);
            return data;
        },

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
