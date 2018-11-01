define(function(require) {
    'use strict';

    var DatagridSettingsListView;
    var template = require('tpl!orodatagrid/templates/datagrid-settings/datagrid-settings.html');
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @class DatagridSettingsListView
     * @extends BaseView
     */
    DatagridSettingsListView = BaseView.extend({
        /**
         * @inheritDoc
         */
        template: template,

        /**
         * @inheritDoc
         */
        autoRender: true,

        /**
         * @inheritDoc
         */
        className: 'dropdown-menu',

        /**
         * @inheritDoc
         */
        events: {
            'click [data-role="datagrid-settings-select-all"]': 'onSelectAll',
            'click [data-role="datagrid-settings-unselect-all"]': 'onUnselectAll',
            'click [data-role="datagrid-settings-reset"]': 'reset'
        },

        /**
         * @inheritDoc
         */
        listen: {
            'change:renderable collection': 'onRenderableChange',
            'layout:reposition mediator': 'adjustListHeight'
        },

        /**
         * @inheritDoc
         */
        constructor: function DatagridSettingsListView() {
            DatagridSettingsListView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            DatagridSettingsListView.__super__.initialize.call(this, options);
            this.filterer = _.bind(options.columnFilterModel.filterer, options.columnFilterModel);
            // to handle renderable change at once for multiple changes
            this.onRenderableChange = _.debounce(this.onRenderableChange, 0);
            this.listenTo(options.columnFilterModel, 'change', this.updateView);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            DatagridSettingsListView.__super__.render.call(this);
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

            this.$('[data-role="datagrid-settings-select-all"]').toggleClass('disabled', !hasUnrenderable);
            this.$('[data-role="datagrid-settings-unselect-all"]').toggleClass('disabled', !hasRenderable);
            this.$('[data-role="datagrid-settings-reset"]').toggleClass('disabled', !hasChanged);

            this.toggleWholeSelectButtons(hasUnrenderable);
        },

        /**
         * Change visibility for actions controls
         * @param {Boolean} state
         */
        toggleWholeSelectButtons: function(state) {
            this.$('[data-role="datagrid-settings-select-all"]').toggleClass('hide-action', !state);
            this.$('[data-role="datagrid-settings-unselect-all"]').toggleClass('hide-action', state);
        },

        /**
         * Fix view height
         */
        adjustListHeight: function() {
            var windowHeight = $(window).height();
            var $wrapper = this.$('[data-role="datagrid-settings-table-wrapper"]');
            var $footerHeight = (this.$('[data-role="datagrid-settings-footer"]').outerHeight() || 0) +
                this.getUIDialogActionHeight() +
                this.getDropdownBottomInnerOffset();
            var rect = $wrapper[0].getBoundingClientRect();
            var margin = (this.$('[data-role="datagrid-settings-table"]').outerHeight(true) - rect.height) / 2;

            $wrapper.css('max-height', Math.max(windowHeight - rect.top - margin - $footerHeight, 120) + 'px');
        },

        /**
         * Get dialog widget actions container height
         * @returns {Number}
         */
        getUIDialogActionHeight: function() {
            var $actions = this.$el.closest('.ui-dialog').find('.ui-dialog-buttonpane');

            return $actions.length ? $actions.outerHeight() : 0;
        },

        /**
         * Get dropdown bottom padding number
         * @returns {Number}
         */
        getDropdownBottomInnerOffset: function() {
            var $dropdown = this.$el.closest('.dropdown-menu');

            return parseInt($dropdown.length
                ? window.getComputedStyle($dropdown.get(0), null).getPropertyValue('padding-bottom')
                : 0
            );
        },

        /**
         * Update view method
         */
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

        /**
         * Change all items from unchecked to checked
         * @param {jQuery.Event} e
         */
        onSelectAll: function(e) {
            e.preventDefault();
            _.each(this._getFilteredModels(), function(model) {
                model.set('renderable', true);
            });
        },

        /**
         * Change all items from checked to unchecked
         * @param {jQuery.Event} e
         */
        onUnselectAll: function(e) {
            e.preventDefault();
            _.each(this._getFilteredModels(), function(model) {
                if (!model.get('disabledVisibilityChange')) {
                    model.set('renderable', false);
                }
            });
        },

        /**
         * Reset selected state to default
         * @param {jQuery.Event} e
         */
        reset: function(e) {
            e.preventDefault();
            _.each(this._getFilteredModels(), function(model) {
                model.set('renderable', model.get('metadata').renderable);
            });
        },

        /**
         * Get visible allowed items
         * @private
         */
        _getFilteredModels: function() {
            return _.filter(this.collection.filter(this.filterer), function(model) {
                return !model.get('disabledVisibilityChange');
            });
        }
    });

    return DatagridSettingsListView;
});
