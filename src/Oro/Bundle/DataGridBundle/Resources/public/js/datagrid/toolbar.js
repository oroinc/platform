define([
    'underscore',
    'backbone',
    'orotranslation/js/translator',
    './pagination-input',
    './visible-items-counter',
    './page-size',
    './pagination-info',
    'orodatagrid/js/datagrid/actions-panel',
    'orodatagrid/js/datagrid/actions-panel-toolbar',
    'orodatagrid/js/datagrid/actions-panel-mass',
    './sorting/dropdown'
], function(
    _,
    Backbone,
    __,
    PaginationInput,
    VisibleItemsCounter,
    PageSize,
    PaginationInfo,
    ActionsPanel,
    ActionsPanelToolbar,
    ActionsPanelMass,
    SortingDropdown
) {
    'use strict';

    const $ = Backbone.$;

    /**
     * Datagrid toolbar widget
     *
     * @export  orodatagrid/js/datagrid/toolbar
     * @class   orodatagrid.datagrid.Toolbar
     * @extends Backbone.View
     */
    const Toolbar = Backbone.View.extend({
        /** @property */
        template: '.datagrid_templates[data-identifier="template-datagrid-toolbar"]',

        /** @property */
        pagination: PaginationInput,

        /** @property */
        itemsCounter: VisibleItemsCounter,

        /** @property */
        pageSize: PageSize,

        /** @property */
        paginationInfo: PaginationInfo.default,

        /** @property */
        sortingDropdown: SortingDropdown,

        /** @property */
        actionsPanel: ActionsPanelToolbar,

        /** @property */
        extraActionsPanel: ActionsPanel,

        /** @property */
        massActionsPanel: ActionsPanelMass,

        /** @property */
        selector: {
            pagination: '[data-grid-pagination]',
            itemsCounter: '[data-grid-items-counter]',
            pagesize: '[data-grid-pagesize]',
            actionsPanel: '[data-grid-actions-panel]',
            extraActionsPanel: '[data-grid-extra-actions-panel]',
            massActionsPanel: '[data-grid-mass-actions-panel]',
            sortingDropdown: '[data-grid-sorting]',
            paginationInfo: '[data-grid-pagination-info]'
        },

        /** @property */
        themeOptions: {
            optionPrefix: 'toolbar'
        },

        /** @property */
        hideItemsCounter: true,

        /**
         * @inheritdoc
         */
        constructor: function Toolbar(options) {
            Toolbar.__super__.constructor.call(this, options);
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

            const optionsItemsCounter = _.defaults({collection: this.collection}, options.itemsCounter);
            options.columns.trigger('configureInitializeOptions', this.itemsCounter, optionsItemsCounter);

            this.subviews = {
                pagination: new this.pagination(_.defaults({collection: this.collection}, options.pagination)),
                itemsCounter: new this.itemsCounter(optionsItemsCounter),
                actionsPanel: new this.actionsPanel(
                    _.extend({className: '', collection: this.collection}, options.actionsPanel)
                ),
                extraActionsPanel: new this.extraActionsPanel({collection: this.collection}),
                massActionsPanel: new this.massActionsPanel({collection: this.collection})
            };

            if (_.result(options.pageSize, 'hide') !== true) {
                this.subviews.pageSize = new this.pageSize(_.defaults({collection: this.collection}, options.pageSize));
            }

            if (_.result(options.paginationInfo, 'show')) {
                this.subviews.paginationInfo = new this.paginationInfo({
                    collection: this.collection,
                    container: this.$(this.selector.paginationInfo),
                    ...options.paginationInfo
                });
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
            if (options.massActionsPanel) {
                this.subviews.massActionsPanel.setActions(options.massActionsPanel);
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
            this.$el.empty();
            this.$el.append(this.template({toolbarPosition: this.$el.data('gridToolbar')}));

            const $pagination = this.subviews.pagination.render().$el;
            $pagination.addClass(this.$(this.selector.pagination).attr('class'));

            this.$(this.selector.pagination).replaceWith($pagination);
            if (this.subviews.pageSize) {
                this.$(this.selector.pagesize).append(this.subviews.pageSize.render().$el);
            }

            if (this.$(this.selector.actionsPanel).length) {
                this.$(this.selector.actionsPanel).append(this.subviews.actionsPanel.render().$el);
            }

            this.$(this.selector.itemsCounter).replaceWith(this.subviews.itemsCounter.render().$el);

            if (this.hideItemsCounter) {
                this.subviews.itemsCounter.$el.hide();
            }

            if (this.subviews.sortingDropdown) {
                this.$(this.selector.sortingDropdown).append(this.subviews.sortingDropdown.render().$el);
            }

            if (this.subviews.paginationInfo) {
                this.$(this.selector.paginationInfo).append(this.subviews.paginationInfo.render().$el);
            }

            if (this.subviews.extraActionsPanel.haveActions()) {
                this.$(this.selector.extraActionsPanel).append(this.subviews.extraActionsPanel.render().$el);
            } else {
                this.$(this.selector.extraActionsPanel).hide();
            }

            if (this.subviews.massActionsPanel.haveActions()) {
                this.$(this.selector.massActionsPanel).replaceWith(this.subviews.massActionsPanel.render().$el);
            }


            return this;
        },

        toggleView() {
            this.$el.removeClass('no-visible-children');

            const noVisibleChildren = Object.values(this.subviews)
                .filter(view => document.contains(view.el))
                .every(view => view.$el.hasClass('hide'));

            this.$el.toggleClass('no-visible-children', noVisibleChildren);

            return this;
        }
    });

    return Toolbar;
});
