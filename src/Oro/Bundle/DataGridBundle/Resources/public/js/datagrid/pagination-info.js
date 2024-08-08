import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orodatagrid/templates/datagrid/pagination-info.html';

const PaginationInfoView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['collection', 'transTemplate']),

    autoRender: true,

    template,

    listen: {
        'add collection': 'render',
        'remove collection': 'render',
        'reset collection': 'render'
    },

    constructor: function PaginationInfoView(...args) {
        PaginationInfoView.__super__.constructor.apply(this, args);
    },

    getTemplateData() {
        const {pageSize, totalRecords, currentPage} = this.collection.state;
        return {
            minRangeThreshold: (currentPage - 1) * pageSize + 1,
            maxRangeThreshold: currentPage * pageSize > totalRecords
                ? totalRecords
                : currentPage * pageSize,
            totalRecords,
            transTemplate: this.transTemplate
        };
    }
});

export default PaginationInfoView;
