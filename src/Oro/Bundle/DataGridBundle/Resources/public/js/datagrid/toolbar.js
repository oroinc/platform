define([
    'underscore',
    'backbone',
    'orotranslation/js/translator',
    './pagination-input',
    './page-size',
    './actions-panel',
    './sorting/dropdown'
], function(_, Backbone, __, PaginationInput, PageSize, ActionsPanel, SortingDropdown) {
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

            this.collection = options.collection;

            this.subviews = {
                pagination: new this.pagination(_.defaults({collection: this.collection}, options.pagination)),
                pageSize: new this.pageSize(_.defaults({collection: this.collection}, options.pageSize)),
                actionsPanel: new this.actionsPanel(_.extend({className: ''}, options.actionsPanel)),
                extraActionsPanel: new this.extraActionsPanel()
            };

            if (options.addSorting) {
                this.subviews.sortingDropdown = new this.sortingDropdown({
                    collection: this.collection,
                    columns: options.columns,
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
            } else {
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
            this.subviews.pagination.enable();
            this.subviews.pageSize.enable();
            this.subviews.actionsPanel.enable();
            this.subviews.extraActionsPanel.enable();
            return this;
        },

        /**
         * Disable toolbar
         *
         * @return {*}
         */
        disable: function() {
            this.subviews.pagination.disable();
            this.subviews.pageSize.disable();
            this.subviews.actionsPanel.disable();
            this.subviews.extraActionsPanel.disable();
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
            this.$el.append(this.template());

            $pagination = this.subviews.pagination.render().$el;
            $pagination.attr('class', this.$(this.selector.pagination).attr('class'));

            this.$(this.selector.pagination).replaceWith($pagination);
            this.$(this.selector.pagesize).append(this.subviews.pageSize.render().$el);
            this.$(this.selector.actionsPanel).append(this.subviews.actionsPanel.render().$el);
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
