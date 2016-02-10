define(function(require) {
    'use strict';

    var ViewSqlQueryPlugin;
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var SqlQueryView = require('../../views/sql-query/sql-query-view');

    ViewSqlQueryPlugin = BasePlugin.extend({
        enable: function() {
            this.view = new SqlQueryView({
                grid: this.main,
                metadata: this.options.data.metadata
            });
            this.view.render();
            this.main.$el.append(this.view.$el);
            this.view.listenTo(this.main, 'content:update', this.view.onUpdate);
            this.view.listenTo(this.main, 'render', this.view.render);
            ViewSqlQueryPlugin.__super__.enable.call(this);
        },
        disable: function() {
            this.view.dispose();
            ViewSqlQueryPlugin.__super__.disable.call(this);
        }
    });

    return ViewSqlQueryPlugin;
});
