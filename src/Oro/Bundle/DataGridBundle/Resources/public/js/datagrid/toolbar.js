define([
    'underscore',
    'backbone',
    'orotranslation/js/translator',
    './pagination-input',
    './page-size',
    './items-counter',
    './actions-panel',
    './sorting/dropdown'
], function(_, Backbone, __, PaginationInput, PageSize, ItemsCounter, ActionsPanel, SortingDropdown) {
    'use strict';

    var Toolbar;
    var $ = Backbone.$;

    /**
     * Datagrid toolbar widget
     *
     * @export  orodatagrid/js/datagrid/toolbar
     * @class   orodatagrid.datagrid.Toolbar
     * @extends Backbone.View
     */
    Toolbar = Backbone.View.extend({
        /** @property */
        template: '#template-datagrid-toolbar',

        /** @property */
        pagination: PaginationInput,

        /** @property */
        itemsCounter: ItemsCounter,

        /** @property */
        pageSize: PageSize,

        /** @property */
        sortingDropdown: SortingDropdown,

        /** @property */
        actionsPanel: ActionsPanel,

        /** @property */
        extraActionsPanel: ActionsPanel,

        /** @property */
        selector: {
            pagination: '[data-grid-pagination]',
            itemsCounter: '[data-grid-items-counter]',
            pagesize: '[data-grid-pagesize]',
            actionsPanel: '[data-grid-actions-panel]',
            extraActionsPanel: '[data-grid-extra-actions-panel]',
            sortingDropdown: '[data-grid-sorting]'
        },

        themeOptions: {
            optionPrefix: 'toolbar'
        },

        /**
         * Initializer.
         *
         * @param {Object} options
         * @param {Backbone.Collection} options.collection
         * @param {Array} options.actions List of actions
         * @throws {TypeError} If "collection" is undefined
         */
        initialize: function(options) {
            options = options || {};

            if (!options.collection) {
                throw new TypeError('"collection" is required');
            }

            var $el = $(options.el);
            var isBottomToolbar = $el && $el.data('gridToolbar') === 'bottom';

            this.collection = options.collection;

            if (isBottomToolbar) {
                this.subviews = {
                    pagination: new this.pagination(_.defaults({collection: this.collection}, options.pagination))
                };
            } else {
                this.subviews = {
                    pagination: new this.pagination(_.defaults({collection: this.collection}, options.pagination)),
                    itemsCounter: new this.itemsCounter(_.defaults({collection: this.collection}, options.itemsCounter)),
                    actionsPanel: new this.actionsPanel(_.extend({className: ''}, options.actionsPanel)),
                    extraActionsPanel: new this.extraActionsPanel()
                };

                if (_.result(options.pageSize, 'hide') !== true) {
                    this.subviews.pageSize = new this.pageSize(_.defaults({collection: this.collection}, options.pageSize));
                }

                if (options.addSorting) {
                    this.subviews.sortingDropdown = new this.sortingDropdown(
                        _.defaults({
                            collection: this.collection,
                            columns: options.columns
                        }, options.addSorting)
                    );
                }
            }

            if (options.actions && this.subviews.actionsPanel) {
                this.subviews.actionsPanel.setActions(options.actions);
            }
            if (options.extraActions && this.subviews.extraActionsPanel) {
                this.subviews.extraActionsPanel.setActions(options.extraActions);
            }

            if (_.has(options, 'enable') && !options.enable) {
                this.disable();
            }
            if (options.hide || this.collection.state.hideToolbar) {
                this.hide();
            }

            if (_.isFunction(options.template)) {
                this.template = options.template;
            } else if (options.template || this.template) {
                this.template = _.template($(options.template || this.template).html());
            }

            Toolbar.__super__.initialize.call(this, options);
        },

        /**
         * Enable toolbar
         *
         * @return {*}
         */
        enable: function() {
            _.invoke(this.subviews, 'enable');
            return this;
        },

        /**
         * Disable toolbar
         *
         * @return {*}
         */
        disable: function() {
            _.invoke(this.subviews, 'disable');
            return this;
        },

        /**
         * Hide toolbar
         *
         * @return {*}
         */
        hide: function() {
            this.$el.hide();
            return this;
        },

        /**
         * Render toolbar with pager and other views
         */
        render: function() {
            var $pagination;
            var selector = this.selector;

            this.$el.empty();
            this.$el.append(this.template());

            if (this.subviews.pagination) {
                $pagination = this.subviews.pagination.render().$el;
                $pagination.attr('class', this.$(this.selector.pagination).attr('class'));
                this.$(selector.pagination).replaceWith($pagination);
            }

            if (this.subviews.pageSize) {
                this.$(selector.pagesize).append(this.subviews.pageSize.render().$el);
            }

            if (this.subviews.actionsPanel) {
                this.$(selector.actionsPanel).append(this.subviews.actionsPanel.render().$el);
            }

            if (this.subviews.itemsCounter) {
                this.$(selector.itemsCounter).replaceWith(this.subviews.itemsCounter.render().$el);
            }
            if (this.subviews.sortingDropdown) {
                this.$(selector.sortingDropdown).append(this.subviews.sortingDropdown.render().$el);
            }

            if (this.subviews.extraActionsPanel) {
                if (this.subviews.extraActionsPanel.haveActions()) {
                    this.$(selector.extraActionsPanel).append(this.subviews.extraActionsPanel.render().$el);
                } else {
                    this.$(selector.extraActionsPanel).hide();
                }
            }

            return this;
        }
    });

    return Toolbar;
});
