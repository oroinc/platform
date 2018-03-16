define(function(require) {
    'use strict';

    var SqlQueryView;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var __ = require('orotranslation/js/translator');
    var messenger = require('oroui/js/messenger');
    require('jquery-ui');

    SqlQueryView = BaseView.extend({
        labels: {
            show_sql: 'oro.report.view_sql.show_sql_query',
            hide_sql: 'oro.report.view_sql.hide_sql_query'
        },

        messages: {
            copy_not_supported: 'oro.report.view_sql.messages.copy_not_supported',
            copied: 'oro.report.view_sql.messages.copied',
            copy_not_successful: 'oro.datagrid.view_sql.messages.copy_not_successful'
        },

        linkLabel: __('oro.report.view_sql.show_sql_query'),

        template: require('tpl!../../../../templates/sql-query-view.html'),

        /**
         * @inheritDoc
         */
        constructor: function SqlQueryView() {
            SqlQueryView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.grid = options.grid;
            this.metadata = options.metadata;
            this.$el.on('click', '#show-sql-query-link', $.proxy(function() {
                this.toggleSqlCode();
            }, this));
            this.$el.on('click', '#copy-sql-query', $.proxy(function() {
                this.copySql();
            }, this));
            SqlQueryView.__super__.initialize.call(this, options);
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
            var $box = this.$el.find('#sql-query-box-' + this.grid.name);
            var $link = this.$el.find('#show-sql-query-link');
            var isShown = $box.is(':visible');
            if (!isShown) {
                $link.text(__(this.labels.hide_sql));
                $box.show();
                var $copyLink = this.$el.find('#copy-sql-query');
                $copyLink.focus();
            } else {
                $link.text(__(this.labels.show_sql));
                $box.hide();
            }
        },

        copySql: function() {
            var sql = document.querySelector('#sql-query-box-' + this.grid.name + ' .sql-query-code');
            var range = document.createRange();
            range.selectNode(sql);
            window.getSelection().addRange(range);
            try {
                var copied = document.execCommand('copy');
                if (copied) {
                    messenger.notificationFlashMessage('success', __(this.messages.copied));
                } else {
                    messenger.notificationFlashMessage('warning', __(this.messages.copy_not_successful));
                }
            } catch (err) {
                messenger.notificationFlashMessage('warning', __(this.messages.copy_not_supported));
            }
            window.getSelection().removeAllRanges();
        }
    });

    return SqlQueryView;
});
