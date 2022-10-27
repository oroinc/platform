define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const BaseView = require('oroui/js/app/views/base/view');
    const ElementValueCopyToClipboardView = require('oroui/js/app/views/element-value-copy-to-clipboard-view');

    const SqlQueryView = BaseView.extend({
        labels: {
            show_sql: 'oro.report.view_sql.show_sql_query',
            hide_sql: 'oro.report.view_sql.hide_sql_query'
        },

        messages: {
            copy_not_supported: 'oro.report.view_sql.messages.copy_not_supported',
            copied: 'oro.report.view_sql.messages.copied',
            copy_not_successful: 'oro.datagrid.view_sql.messages.copy_not_successful'
        },

        template: require('tpl-loader!../../../../templates/sql-query-view.html'),

        /**
         * @inheritdoc
         */
        constructor: function SqlQueryView(options) {
            SqlQueryView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.sql = options.sql;
            this.copyTargetElementId = _.uniqueId('sql-query-code');

            SqlQueryView.__super__.initialize.call(this, options);
        },

        getTemplateData: function() {
            return {
                sql: this.sql,
                collapseLabel: __(this.labels.hide_sql),
                expandLabel: __(this.labels.show_sql),
                togglerId: _.uniqueId('toggler-'),
                collapseId: _.uniqueId('collapse-'),
                copyTargetElementId: this.copyTargetElementId
            };
        },

        /**
         * @inheritdoc
         */
        render: function() {
            this.removeSubview('clipboard-view');

            SqlQueryView.__super__.render.call(this);

            this.subview('clipboard-view', new ElementValueCopyToClipboardView({
                autoRender: true,
                messages: this.messages,
                el: this.$('[data-role=copy-sql-query]'),
                elementSelector: '#' + this.copyTargetElementId
            }));

            return this;
        },

        updateSQL: function(sql) {
            this.$('[data-role="sql-query"]').text(sql);
        }
    });

    return SqlQueryView;
});
