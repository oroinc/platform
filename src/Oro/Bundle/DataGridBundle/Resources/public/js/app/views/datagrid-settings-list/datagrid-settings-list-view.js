define(function(require) {
    'use strict';

    const template = require('tpl-loader!orodatagrid/templates/datagrid-settings/datagrid-settings.html');
    const _ = require('underscore');
    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');

    /**
     * @class DatagridSettingsListView
     * @extends BaseView
     */
    const DatagridSettingsListView = BaseView.extend({
        /**
         * @inheritdoc
         */
        template: template,

        /**
         * @inheritdoc
         */
        autoRender: true,

        /**
         * @inheritdoc
         */
        className: 'dropdown-menu',

        /**
         * @inheritdoc
         */
        events: {
            'click [data-role="datagrid-settings-select-all"]': 'onSelectAll',
            'click [data-role="datagrid-settings-unselect-all"]': 'onUnselectAll',
            'click [data-role="datagrid-settings-reset"]': 'reset',
            'click [data-role="close"]': 'closeDropdown'
        },

        /**
         * @inheritdoc
         */
        listen: {
            'change:renderable collection': 'onRenderableChange',
            'layout:reposition mediator': 'adjustListHeight'
        },

        /**
         * @inheritdoc
         */
        constructor: function DatagridSettingsListView(options) {
            DatagridSettingsListView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            DatagridSettingsListView.__super__.initialize.call(this, options);
            this.filterer = options.columnFilterModel.filterer.bind(options.columnFilterModel);
            // to handle renderable change at once for multiple changes
            this.onRenderableChange = _.debounce(this.onRenderableChange, 0);
            this.listenTo(options.columnFilterModel, 'change', this.updateView);
        },

        /**
         * @inheritdoc
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
            const models = this._getFilteredModels();
            const hasUnrenderable = Boolean(_.find(models, function(model) {
                return !model.get('renderable');
            }));
            const hasRenderable = Boolean(_.find(models, function(model) {
                return model.get('renderable');
            }));
            const hasChanged = Boolean(_.find(models, function(model) {
                return model.get('renderable') !== model.get('metadata').renderable;
            }));
            const actions = [{
                $el: this.$('[data-role="datagrid-settings-select-all"]'),
                toApply: !hasUnrenderable
            }, {
                $el: this.$('[data-role="datagrid-settings-unselect-all"]'),
                toApply: !hasRenderable
            }, {
                $el: this.$('[data-role="datagrid-settings-reset"]'),
                toApply: !hasChanged
            }];

            for (const {$el, toApply} of actions) {
                $el.toggleClass('disabled', toApply);

                if ($el.is(':button')) {
                    $el.attr('disabled', toApply);
                } else {
                    $el.attr('aria-disabled', toApply);
                }
            }

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
            const windowHeight = $(window).height();
            const $wrapper = this.$('[data-role="datagrid-settings-table-wrapper"]');
            const $footerHeight = (this.$('[data-role="datagrid-settings-footer"]').outerHeight() || 0) +
                this.getUIDialogActionHeight() +
                this.getDropdownBottomInnerOffset();
            const rect = $wrapper[0].getBoundingClientRect();
            const margin = (this.$('[data-role="datagrid-settings-table"]').outerHeight(true) - rect.height) / 2;

            $wrapper.css('max-height', Math.max(windowHeight - rect.top - margin - $footerHeight, 120) + 'px');
        },

        /**
         * Get dialog widget actions container height
         * @returns {Number}
         */
        getUIDialogActionHeight: function() {
            const $actions = this.$el.closest('.ui-dialog').find('.ui-dialog-buttonpane');

            return $actions.length ? $actions.outerHeight() : 0;
        },

        /**
         * Get dropdown bottom padding number
         * @returns {Number}
         */
        getDropdownBottomInnerOffset: function() {
            const $dropdown = this.$el.closest('.dropdown-menu');

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
        },

        /**
         * Extra handler for close dropdown
         */
        closeDropdown: function() {
            this.$el.trigger('tohide.bs.dropdown');
        }
    });

    return DatagridSettingsListView;
});
