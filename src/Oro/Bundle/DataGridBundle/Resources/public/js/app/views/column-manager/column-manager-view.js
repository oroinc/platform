define(function(require) {
    'use strict';

    var ColumnManagerView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    ColumnManagerView = BaseView.extend({
        template: require('tpl!orodatagrid/templates/column-manager/column-manager.html'),
        autoRender: true,
        className: 'dropdown-menu',
        events: {
            'click [data-role="column-manager-select-all"]': 'onSelectAll'
        },

        listen: {
            'change:renderable collection': 'updateView'
        },

        initialize: function(options) {
            ColumnManagerView.__super__.initialize.call(this, options);
            this.filterer = _.bind(options.columnFilterModel.filterer, options.columnFilterModel);
            this.updateView = _.debounce(this.updateView, 50);
            this.listenTo(options.columnFilterModel, 'change', this.updateView);
        },

        render: function() {
            ColumnManagerView.__super__.render.call(this);
            this.updateView();
            return this;
        },

        updateView: function() {
            var models = this._getFilteredModels();
            var hasUnrenderable = Boolean(_.find(models, function(model) {return !model.get('renderable')}));
            this.$('[data-role="column-manager-select-all"]').toggleClass('disabled', !hasUnrenderable);
        },

        onSelectAll: function(e) {
            e.preventDefault();
            _.each(this._getFilteredModels(), function(model) {
                model.set('renderable', true);
            });
            this.updateView();
        },

        _getFilteredModels: function() {
            return _.filter(this.collection.filter(this.filterer), function(model) {
                return !model.get('disabledVisibilityChange');
            });
        }
    });

    return ColumnManagerView;
});
