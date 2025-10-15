import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import SqlQueryView from '../../views/sql-query/sql-query-view';

const ViewSqlQueryPlugin = BasePlugin.extend({
    enable: function() {
        this.view = new SqlQueryView({
            autoRender: true,
            el: '[data-role="sql-query-panel"]',
            sql: this.options.data.metadata.stored_sql.sql
        });

        this.listenTo(this.main, 'content:update', this.onGridContentUpdate);

        ViewSqlQueryPlugin.__super__.enable.call(this);
    },

    onGridContentUpdate: function() {
        this.view.updateSQL(this.main.metadata.stored_sql.sql);
    },

    disable: function() {
        this.view.dispose();
        delete this.view;

        ViewSqlQueryPlugin.__super__.disable.call(this);
    }
});

export default ViewSqlQueryPlugin;
