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
    require('jquery.select2');

    RecentEmailsContentView = BaseWidgetSetupView.extend({
        template: require('tpl!oroemail/templates/sidebar-widget/recent-emails/recent-emails-setup-view.html'),

        foldersData: null,

        initialize: function() {
            RecentEmailsContentView.__super__.initialize.apply(this, arguments);
            $.getJSON(routing.generate('oro_api_get_emailorigins'),
                _.bind(function(data) {
                    this.foldersData = this.parseFoldersData(data);
                    this.foldersData.unshift({id: 0, text: __('oro.email.recent_emails_widget.all_folders')});
                    this.render();
                }, this)
            );
        },

        parseFoldersData: function(data) {
            var mailboxes = [];
            _.each(data, function(mailbox) {
                var text;
                var folders;
                if (mailbox.active) {
                    text = mailbox.properties.user;
                    folders = mailbox.folders.filter(function(folder) {
                            return Boolean(folder.syncEnabled);
                        }).map(function(folder) {
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
            var info = {
                name: '',
                mailbox: ''
            };
            id = Number(id);
            if (this.foldersData !== null && id) {
                _.each(this.foldersData, function(mailbox) {
                    var folder;
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
            this.$el.find('[name=defaultActionId]').select2();
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

            this.initPopover(this.$el);
        },

        fetchFromData: function() {
            var data = RecentEmailsContentView.__super__.fetchFromData.call(this);
            data.limit = Number(data.limit);
            return data;
        },

        onSubmit: function() {
            var settings = this.fetchFromData();
            var folderInfo = this.getFolderInfo(settings.folderId);
            settings.folderName = folderInfo.name;
            settings.mailboxName = folderInfo.mailbox;
            if (!tools.isEqualsLoosely(settings, this.model.get('settings'))) {
                this.model.set('settings', settings);
            }
            this.trigger('close');
        },

        initPopover: function (container) {
            var $items = container.find('[data-toggle="popover"]').filter(function() {
                // skip already initialized popovers
                return !$(this).data('popover');
            });
            $items.not('[data-close="false"]').each(function(i, el) {
                //append close link
                var content = $(el).data('content');
                content += '<i class="icon-remove popover-close"></i>';
                $(el).data('content', content);
            });

            $items.popover({
                animation: false,
                delay: {show: 0, hide: 0},
                html: true,
                container: false,
                trigger: 'manual'
            }).on('click.popover', function(e) {
                $(this).popover('toggle');
                e.preventDefault();
            });

            container.on('click.popover-hide', function(e) {
                    var $target = $(e.target);
                    $items.each(function() {
                        //the 'is' for buttons that trigger popups
                        //the 'has' for icons within a button that triggers a popup
                        if (
                            !$(this).is($target) &&
                            $(this).has($target).length === 0 &&
                            ($('.popover').has($target).length === 0 || $target.hasClass('popover-close'))
                        ) {
                            $(this).popover('hide');
                        }
                    });
                }).on('click.popover-prevent', '.popover', function(e) {
                if (e.target.tagName.toLowerCase() !== 'a') {
                    e.preventDefault();
                }
            }).on('focus.popover-hide', 'select, input, textarea', function() {
                $items.popover('hide');
            });
        }
    });

    return RecentEmailsContentView;
});
