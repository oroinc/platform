define(function(require) {
    'use strict';

    var ShowSqlSourcePlugin;
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var $ = require('jquery');
    var ShowSqlSourceView = require('../../views/sql-source/show-sql-source-view');

    ShowSqlSourcePlugin = BasePlugin.extend({
        enable: function() {
            this.view = new ShowSqlSourceView({
                grid: this.main,
                metadata: this.options.data.metadata
            });
            this.view.render();
            this.main.$el.append(this.view.$el);
            this.view.listenTo(this.main, 'content:update', this.view.onUpdate);
            this.view.listenTo(this.main, 'render', this.view.render);
            ShowSqlSourcePlugin.__super__.enable.call(this);
        },
        disable: function() {
            this.view.dispose();
            ShowSqlSourcePlugin.__super__.disable.call(this);
        }
    });

    return ShowSqlSourcePlugin;
});
