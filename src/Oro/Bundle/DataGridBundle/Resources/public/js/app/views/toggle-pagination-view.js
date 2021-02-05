import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orodatagrid/templates/datagrid/toggle-pagination.html';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';

/**
 * Updates grid's url params with options to turn on/off data pagination
 */
const TogglePaginationView = BaseView.extend({
    autoRender: true,

    enabled: true,

    grid: null,

    maxPageSize: void 0,

    className: 'datagrid-toggle-pagination',

    translationPrefix: 'oro.datagrid.btn',

    template,

    events: {
        'click [data-role="pagination-toggler"]': 'onClick'
    },

    constructor: function TogglePaginationView(options) {
        TogglePaginationView.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        _.extend(this, _.pick(options, ['translationPrefix']));

        this.grid = options.datagrid;

        const {items} = this.grid.gridOptions.toolbarOptions.pageSize;
        this.maxPageSize = Math.max(...items.map(item => item.size || item));

        this.listenTo(this.grid.collection, 'updateState', this.render);
        this.listenTo(this.grid, 'disable', this.disable);
        this.listenTo(this.grid, 'enable', this.enable);

        TogglePaginationView.__super__.initialize.call(this, options);
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        delete this.grid;

        TogglePaginationView.__super__.dispose.call(this);
    },

    getTemplateData: function() {
        const {pageSize: currentPageSize} = this.grid.collection.state;
        const isMaxPageSize = currentPageSize === this.maxPageSize;
        const translationPrefix = `${this.translationPrefix}.${isMaxPageSize ? 'show_less' : 'show_all'}`;

        return {
            enabled: this.enabled,
            isMaxPageSize,
            label: __(`${translationPrefix}.label`),
            ariaLabel: __(`${translationPrefix}.aria_label`)
        };
    },

    disable: function() {
        this.enabled = false;
        this.render();
        return this;
    },

    enable: function() {
        this.enabled = true;
        this.render();
        return this;
    },

    onClick: function() {
        if (!this.enabled) {
            return;
        }
        this.togglePagination();
    },

    togglePagination: function() {
        const {pageSize: initialPageSize} = this.grid.collection.initialState;
        const {pageSize: currentPageSize} = this.grid.collection.state;
        const pageSize = currentPageSize === this.maxPageSize ? initialPageSize : this.maxPageSize;
        this.grid.collection.setPageSize(pageSize);
    }
}, {
    isVisible(grid) {
        const {pageSize: initialPageSize} = grid.collection.initialState;
        const {pageSize: currentPageSize, totalPages, totalRecords} = grid.collection.state;
        const {items} = grid.gridOptions.toolbarOptions.pageSize;
        const isMaxPageSize = currentPageSize === Math.max(...items.map(item => item.size || item));

        return totalPages > 1 || isMaxPageSize && totalRecords > initialPageSize;
    }
});

export default TogglePaginationView;
