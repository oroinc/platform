import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orodatagrid/templates/datagrid/toggle-pagination.html';
import __ from 'orotranslation/js/translator';

/**
 * Updates grid's url params with options to turn on/off data pagination
 */
const TogglePaginationView = BaseView.extend({
    autoRender: true,

    enabled: true,

    grid: null,

    maxPageSize: void 0,

    template,

    events: {
        'click [data-role="pagination-toggler"]': 'onClick'
    },

    constructor: function TogglePaginationView(options) {
        TogglePaginationView.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
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
        const {pageSize: initialPageSize} = this.grid.collection.initialState;
        const {pageSize: currentPageSize, totalPages, totalRecords} = this.grid.collection.state;
        const isMaxPageSize = currentPageSize === this.maxPageSize;
        const translationPrefix = `oro.frontend.shoppinglist.btn.${isMaxPageSize ? 'show_less' : 'show_all'}`;

        return {
            enabled: this.enabled,
            visible: totalPages > 1 || isMaxPageSize && totalRecords > initialPageSize,
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
        this.grid.collection.fetch({reset: true});
    },

    togglePagination: function() {
        const {pageSize: initialPageSize} = this.grid.collection.initialState;
        const {pageSize: currentPageSize} = this.grid.collection.state;
        const pageSize = currentPageSize === this.maxPageSize ? initialPageSize : this.maxPageSize;
        this.grid.collection.setPageSize(pageSize);
    }
});

export default TogglePaginationView;
