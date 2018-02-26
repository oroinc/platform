define(function(require) {
    'use strict';

    var ColumnManagerView;
    var template = require('tpl!orodatagrid/templates/column-manager/column-manager.html');
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    ColumnManagerView = BaseView.extend({
        template: template,

        autoRender: true,

        className: 'dropdown-menu',

        events: {
            'click [data-role="column-manager-select-all"]': 'onSelectAll',
            'click [data-role="column-manager-unselect-all"]': 'onunselectAll',
            'click [data-role="column-manager-reset"]': 'reset'
        },

        listen: {
            'change:renderable collection': 'onRenderableChange',
            'layout:reposition mediator': 'adjustListHeight'
        },

        /**
         * @inheritDoc
         */
        constructor: function ColumnManagerView() {
            ColumnManagerView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
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
            var hasUnrenderable = Boolean(_.find(models, function(model) {
                return !model.get('renderable');
            }));
            var hasRenderable = Boolean(_.find(models, function(model) {
                return model.get('renderable');
            }));
            var hasChanged = Boolean(_.find(models, function(model) {
                return model.get('renderable') !== model.get('metadata').renderable;
            }));

            this.$('[data-role="column-manager-select-all"]').toggleClass('disabled', !hasUnrenderable);
            this.$('[data-role="column-manager-unselect-all"]').toggleClass('disabled', !hasRenderable);
            this.$('[data-role="column-manager-reset"]').toggleClass('disabled', !hasChanged);
        },

        adjustListHeight: function() {
            var windowHeight = $(window).height();
            var $wrapper = this.$('[data-role="column-manager-table-wrapper"]');
            var $footerHeight = this.$('[data-role="column-manager-footer"]').outerHeight() || 0;
            var rect = $wrapper[0].getBoundingClientRect();
            var margin = (this.$('[data-role="column-manager-table"]').outerHeight(true) - rect.height) / 2;
            $wrapper.css('max-height', Math.max(windowHeight - rect.top - margin - $footerHeight, 40) + 'px');
        },

        updateStateView: function() {
            this.adjustListHeight();
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
