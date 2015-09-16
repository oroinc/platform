define(function(require) {
    'use strict';

    var RecentEmailsContentView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BaseWidgetSetupView = require('orosidebar/js/app/views/base-widget/base-widget-setup-view');
    var Select2Component = require('oroform/js/app/components/select2-component');

    RecentEmailsContentView = BaseWidgetSetupView.extend({
        template: require('tpl!oroemail/templates/sidebar-widget/recent-emails/recent-emails-setup-view.html'),

        widgetTitle: function() {
            return __('oro.email.recent_emails_widget.settings');
        },

        validation: {
            perPage: {
                NotBlank: {},
                Regex: {pattern: '/^\\d+$/'},
                Number: {min: 1, max: 20}
            }
        },

        render: function() {
            RecentEmailsContentView.__super__.render.apply(this, arguments);
            this.$el.find('.select2').each(_.bind(function(i, el) {
                var component;
                var options = {configs: {}};
                options._sourceElement = $(el);
                component = new Select2Component(options);
                this.pageComponent('select2' + $(el).attr('name'), component, el);
            }, this));
        },

        fetchFromData: function() {
            var data = RecentEmailsContentView.__super__.fetchFromData.call(this);
            data.perPage = Number(data.perPage);
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
