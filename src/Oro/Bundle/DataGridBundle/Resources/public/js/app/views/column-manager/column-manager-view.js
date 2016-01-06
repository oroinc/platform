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
            'click [data-role="column-manager-select-all"]': 'onSelectAll',
            'shown.bs.dropdown': 'onOpen'
        },

        listen: {
            'change:renderable collection': 'onRenderableChange'
        },

        initialize: function(options) {
            ColumnManagerView.__super__.initialize.call(this, options);
            this.filterer = _.bind(options.columnFilterModel.filterer, options.columnFilterModel);
            // to handle renderable change at once for multiple changes
            this.onRenderableChange = _.debounce(this.onRenderableChange, 0);
            this.listenTo(options.columnFilterModel, 'change', this.updateView);
        },

        render: function() {
            ColumnManagerView.__super__.render.call(this);
            this.updateView();
            return this;
        },

        /**
         * Update filter view from its state
         */
        updateView: function() {
            var models = this._getFilteredModels();
            var hasUnrenderable = Boolean(_.find(models, function(model) {return !model.get('renderable');}));
            this.$('[data-role="column-manager-select-all"]').toggleClass('disabled', !hasUnrenderable);
        },

        /**
         * Handles renderable change of column models
         *  - updates the view
         */
        onRenderableChange: function() {
            this.updateView();
        },

        onSelectAll: function(e) {
            e.preventDefault();
            _.each(this._getFilteredModels(), function(model) {
                model.set('renderable', true);
            });
        },

        onOpen: function() {
            var rect = this.el.getBoundingClientRect();
            this.$el.css({
                maxWidth: rect.right + 'px'
            });
        },

        _getFilteredModels: function() {
            return _.filter(this.collection.filter(this.filterer), function(model) {
                return !model.get('disabledVisibilityChange');
            });
        }
    });

    return ColumnManagerView;
});
