define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const tools = require('oroui/js/tools');
    const routing = require('routing');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const BaseWidgetSetupView = require('orosidebar/js/app/views/base-widget/base-widget-setup-view');
    require('jquery.select2');

    const RecentEmailsContentView = BaseWidgetSetupView.extend({
        template: require('tpl-loader!oroemail/templates/sidebar-widget/recent-emails/recent-emails-setup-view.html'),

        foldersData: null,

        /**
         * @inheritdoc
         */
        constructor: function RecentEmailsContentView(options) {
            RecentEmailsContentView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            RecentEmailsContentView.__super__.initialize.call(this, options);
            $.getJSON(routing.generate('oro_email_emailorigin_list'),
                data => {
                    this.foldersData = this.parseFoldersData(data);
                    this.foldersData.unshift({id: 0, text: __('oro.email.recent_emails_widget.all_folders')});
                    this.render();
                }
            );
        },

        parseFoldersData: function(data) {
            let mailboxes = [];
            _.each(data, function(mailbox, text) {
                let folders;
                if (mailbox.active) {
                    folders =
                        _.where(mailbox.folder, {syncEnabled: true})
                            .map(function(folder) {
                                return {
                                    id: Number(folder.id),
                                    text: folder.fullName
                                };
                            });
                    if (folders.length > 0) {
                        if (text) {
                            mailboxes.push({
                                text: text,
                                children: folders
                            });
                        } else {
                            mailboxes = mailboxes.concat(folders);
                        }
                    }
                }
            });
            return mailboxes;
        },

        getFolderInfo: function(id) {
            const info = {
                name: '',
                mailbox: ''
            };
            id = Number(id);
            if (this.foldersData !== null && id) {
                _.each(this.foldersData, function(mailbox) {
                    let folder;
                    if (info.name.length === 0) {
                        if ('children' in mailbox) {
                            folder = _.find(mailbox.children, function(folder) {
                                return folder.id === id;
                            });
                            if (folder !== void 0) {
                                info.name = folder.text;
                                info.mailbox = mailbox.text;
                            }
                        } else if (mailbox.id === id) {
                            info.name = mailbox.text;
                        }
                    }
                });
            }
            return info;
        },

        getTemplateData: function() {
            const data = this.model.toJSON();
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
            RecentEmailsContentView.__super__.render.call(this);
            const loadingView = this.subview('loading');
            this.$el.find('[name=defaultActionId]').inputWidget('create', 'select2');
            if (this.foldersData !== null) {
                if (loadingView) {
                    loadingView.hide();
                }
                this.$el.find('[name=folderId]').inputWidget('create', 'select2', {
                    initializeOptions: {data: this.foldersData}
                });
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
            const data = RecentEmailsContentView.__super__.fetchFromData.call(this);
            data.limit = Number(data.limit);
            return data;
        },

        onSubmit: function() {
            const settings = this.fetchFromData();
            const folderInfo = this.getFolderInfo(settings.folderId);
            settings.folderName = folderInfo.name;
            settings.mailboxName = folderInfo.mailbox;
            if (!tools.isEqualsLoosely(settings, this.model.get('settings'))) {
                this.model.set('settings', settings);
            }
            this.trigger('close');
        }
    });

    return RecentEmailsContentView;
});
