define([
    'underscore',
    'backbone',
    'orotranslation/js/translator',
    './pagination-input',
    './visible-items-counter',
    './page-size',
    './actions-panel',
    './sorting/dropdown'
], function(_, Backbone, __, PaginationInput, VisibleItemsCounter, PageSize, ActionsPanel, SortingDropdown) {
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
        itemsCounter: VisibleItemsCounter,

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

        /** @property */
        themeOptions: {
            optionPrefix: 'toolbar'
        },

        /** @property */
        hideItemsCounter: true,

        /**
         * @inheritDoc
         */
        constructor: function Toolbar() {
            Toolbar.__super__.constructor.apply(this, arguments);
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

            this.collection = options.collection;

            var optionsiIemsCounter = _.defaults({collection: this.collection}, options.itemsCounter);
            options.columns.trigger('configureInitializeOptions', this.itemsCounter, optionsiIemsCounter);

            this.subviews = {
                pagination: new this.pagination(_.defaults({collection: this.collection}, options.pagination)),
                itemsCounter: new this.itemsCounter(optionsiIemsCounter),
                actionsPanel: new this.actionsPanel(_.extend({className: ''}, options.actionsPanel)),
                extraActionsPanel: new this.extraActionsPanel()
            };

            if (_.result(options.pageSize, 'hide') !== true) {
                this.subviews.pageSize = new this.pageSize(_.defaults({collection: this.collection}, options.pageSize));
            }

            if (options.addSorting) {
                this.subviews.sortingDropdown = new this.sortingDropdown({
                    collection: this.collection,
                    columns: options.columns
                });
            }

            if (options.actions) {
                this.subviews.actionsPanel.setActions(options.actions);
            }
            if (options.extraActions) {
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

            if (!_.isUndefined(options.hideItemsCounter)) {
                this.hideItemsCounter = options.hideItemsCounter;
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
            this.$el.empty();
            this.$el.append(this.template({toolbarPosition: this.$el.data('gridToolbar')}));

            $pagination = this.subviews.pagination.render().$el;
            $pagination.attr('class', this.$(this.selector.pagination).attr('class'));

            this.$(this.selector.pagination).replaceWith($pagination);
            if (this.subviews.pageSize) {
                this.$(this.selector.pagesize).append(this.subviews.pageSize.render().$el);
            }
            this.$(this.selector.actionsPanel).append(this.subviews.actionsPanel.render().$el);

            this.$(this.selector.itemsCounter).replaceWith(this.subviews.itemsCounter.render().$el);

            if (this.hideItemsCounter) {
                this.subviews.itemsCounter.$el.hide();
            }

            if (this.subviews.sortingDropdown) {
                this.$(this.selector.sortingDropdown).append(this.subviews.sortingDropdown.render().$el);
            }

            if (this.subviews.extraActionsPanel.haveActions()) {
                this.$(this.selector.extraActionsPanel).append(this.subviews.extraActionsPanel.render().$el);
            } else {
                this.$(this.selector.extraActionsPanel).hide();
            }

            return this;
        }
    });

    return Toolbar;
});
