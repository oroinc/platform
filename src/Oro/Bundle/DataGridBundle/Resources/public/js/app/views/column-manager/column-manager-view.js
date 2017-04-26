define(function(require) {
    'use strict';

    var ColumnManagerView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var module = require('module');
    var config = module.config();

    config = _.extend({
        templateSelector: null
    }, config);

    ColumnManagerView = BaseView.extend({
        template: require('tpl!orodatagrid/templates/column-manager/column-manager.html'),
        templateSelector: config.templateSelector,
        autoRender: true,
        className: 'dropdown-menu',
        events: {
            'click [data-role="column-manager-select-all"]': 'onSelectAll',
            'click [data-role="column-manager-unselect-all"]': 'onunselectAll',
            'click [data-role="column-manager-reset"]': 'reset'
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
            var hasRenderable = Boolean(_.find(models, function(model) {return model.get('renderable');}));
            var hasChanged = Boolean(_.find(models, function(model) {
                return model.get('renderable') !== model.get('metadata').renderable;
            }));

            this.$('[data-role="column-manager-select-all"]').toggleClass('disabled', !hasUnrenderable);
            this.$('[data-role="column-manager-unselect-all"]').toggleClass('disabled', !hasRenderable);
            this.$('[data-role="column-manager-reset"]').toggleClass('disabled', !hasChanged);
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

        onunselectAll: function(e) {
            e.preventDefault();
            _.each(this._getFilteredModels(), function(model) {
                if (!model.get('disabledVisibilityChange')) {
                    model.set('renderable', false);
                }
            });
        },

        reset: function(e) {
            e.preventDefault();
            _.each(this._getFilteredModels(), function(model) {
                model.set('renderable', model.get('metadata').renderable);
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
