define(function(require) {
    'use strict';

    var StoredSqlView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var template = require('tpl!orodatagrid/templates/inline-editing/error-holder.html');
    require('jquery-ui');

    StoredSqlView = BaseView.extend({
        template: require('tpl!../../../../templates/stored-sql-view.html'),
        initialize: function(options) {
            this.grid = options.grid;
            this.metadata = options.metadata;
            StoredSqlView.__super__.initialize.call(this, options);
        },

        getTemplateData: function() {
            return {sql: this.metadata.stored_sql.sql};
        },

        onUpdate: function() {
            this.metadata = this.grid.metadata;
            this.render();
        }
    });

    return StoredSqlView;
});
