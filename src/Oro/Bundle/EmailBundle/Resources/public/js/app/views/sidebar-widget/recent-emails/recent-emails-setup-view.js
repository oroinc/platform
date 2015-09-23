define(function(require) {
    'use strict';

    var RecentEmailsContentView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var tools = require('oroui/js/tools');
    var routing = require('routing');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseWidgetSetupView = require('orosidebar/js/app/views/base-widget/base-widget-setup-view');

    RecentEmailsContentView = BaseWidgetSetupView.extend({
        template: require('tpl!oroemail/templates/sidebar-widget/recent-emails/recent-emails-setup-view.html'),

        foldersData: null,

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
                var text;
                if (mailbox.active) {
                    text = mailbox.properties.user;
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
                    } else {
                        mailboxes = mailboxes.concat(mailbox.folders.map(function(folder) {
                            return {
                                id: folder.id,
                                text: folder.fullName
                            };
                        }));
                    }
                }
            });
            return mailboxes;
        },

        getFolderInfo: function(id) {
            var info = {
                name: '',
                mailbox: ''
            };
            if (this.foldersData !== null && id) {
                _.each(this.foldersData, function(mailbox) {
                    var folder;
                    if (info.name.length === 0) {
                        if ('children' in mailbox) {
                            folder = _.find(mailbox.children, function(folder) {
                                return +folder.id === +id;
                            });
                            if (folder !== void 0) {
                                info.name = folder.text;
                                info.mailbox = mailbox.text;
                            }
                        } else if (+mailbox.id === +id) {
                            info.name = mailbox.text;
                        }
                    }
                });
            }
            return info;
        },

        getTemplateData: function() {
            var data = this.model.toJSON();
            data.foldersData = this.foldersData;
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
            var loadingView = this.subview('loading');
            this.$el.find('[name=actionId]').select2();
            if (this.foldersData !== null) {
                if (loadingView) {
                    loadingView.hide();
                }
                this.$el.find('[name=folderId]').select2({data: this.foldersData});
            } else {
                if (!loadingView) {
                    this.subview('loading', new LoadingMaskView({
                        container: this.$('.recent-emails-setup')
                    }));
                }
                this.subview('loading').show();
            }
        },

        fetchFromData: function() {
            var data = RecentEmailsContentView.__super__.fetchFromData.call(this);
            data.limit = Number(data.limit);
            return data;
        },

        onSubmit: function() {
            var title = __('oro.email.recent_emails_widget.title_all_folders');
            var settings = this.fetchFromData();
            var folderInfo = this.getFolderInfo(settings.folderId);
            settings.folderName = folderInfo.name;
            settings.mailboxName = folderInfo.mailbox;
            if (settings.folderName) {
                title = settings.folderName;
                if (settings.mailboxName) {
                    title += ' - ' + settings.mailboxName;
                }
            }
            if (!tools.isEqualsLoosely(settings, this.model.get('settings'))) {
                this.model.set({
                    'title': title,
                    'settings': settings
                });
            }
            this.trigger('close');
        }
    });

    return RecentEmailsContentView;
});
