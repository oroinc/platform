define(function(require) {
    'use strict';

    var StoredSqlView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var __ = require('orotranslation/js/translator');
    var messenger = require('oroui/js/messenger');
    require('jquery-ui');

    StoredSqlView = BaseView.extend({
        labels: {
            show_sql: 'oro.datagrid.show_sql.show_sql_code',
            hide_sql: 'oro.datagrid.show_sql.hide_sql_code'
        },
        messages: {
            copy_not_supported: 'oro.datagrid.show_sql.messages.copy_not_supported',
            copied: 'oro.datagrid.show_sql.messages.copied',
            copy_not_successful: 'oro.datagrid.show_sql.messages.copy_not_successful'
        },
        linkLabel: __('oro.datagrid.show_sql.show_sql_code'),
        template: require('tpl!../../../../templates/stored-sql-view.html'),
        initialize: function(options) {
            this.grid = options.grid;
            this.metadata = options.metadata;
            this.$el.on('click', '#show-sql-source-link', $.proxy(function() {
                this.toggleSqlCode();
            }, this));
            this.$el.on('click', '#copy-sql-source', $.proxy(function() {
                this.copySql();
            }, this));
            StoredSqlView.__super__.initialize.call(this, options);
        },
        getTemplateData: function() {
            return {
                sql: this.metadata.stored_sql.sql,
                linkLabel: this.linkLabel,
                gridName: this.grid.name
            };

        },
        onUpdate: function() {
            this.metadata = this.grid.metadata;
            this.render();
        },
        toggleSqlCode: function() {
            var $box = this.$el.find('#sql-source-box-' + this.grid.name);
            var $link = this.$el.find('#show-sql-source-link');
            var isShown = $box.is(":visible");
            if (!isShown) {
                $link.text(__(this.labels.hide_sql));
                $box.show();
                var $copyLink = this.$el.find('#copy-sql-source');
                $copyLink.focus();
            } else {
                $link.text(__(this.labels.show_sql));
                $box.hide();
            }
        },
        copySql: function () {
            var sql = document.querySelector('#sql-source-box-' + this.grid.name+ ' .sql-source-code');
            var range = document.createRange();
            range.selectNode(sql);
            window.getSelection().addRange(range);
            try {
                document.execCommand('copy')
                    ? messenger.notificationFlashMessage('success', __(this.messages.copied))
                    : messenger.notificationFlashMessage('warning', __(this.messages.copy_not_successful))

            } catch (err) {
                messenger.notificationFlashMessage('warning', __(this.messages.copy_not_supported));
            }
            window.getSelection().removeAllRanges();
        }
    });

    return StoredSqlView;
});
