/*jslint nomen:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    './pagination',
    'jquery.numeric'
], function($, _, Pagination) {
    'use strict';

    var PaginationInput;

    /**
     * Datagrid pagination with input field
     *
     * @export  orodatagrid/js/datagrid/pagination-input
     * @class   orodatagrid.datagrid.PaginationInput
     * @extends orodatagrid.datagrid.Pagination
     */
    PaginationInput = Pagination.extend({
        /** @property */
        template: '#template-datagrid-toolbar-pagination-input',

        /** @property */
        events: {
            "click a": "onChangePage",
            "blur input": "onChangePageByInput",
            "change input": "onChangePageByInput",
            'keyup input': function(e) {
                if (e.which === 13) {
                    // fix for IE 8, bacause change event is not fired when enter is pressed
                    this.onChangePageByInput(e);
                }
            }
        },

        /** @property */
        windowSize: 0,

        /**
         * Apply change of pagination page input
         *
         * @param {Event} e
         */
        onChangePageByInput: function(e) {
            e.preventDefault();

            var pageIndex = parseInt($(e.target).val(), 10);
            var collection = this.collection;
            var state = collection.state;

            if (_.isNaN(pageIndex)) {
                $(e.target).val(state.currentPage);
                return;
            }

            pageIndex = state.firstPage == 0 ? pageIndex - 1  : pageIndex;
            if (pageIndex < state.firstPage) {
                pageIndex = state.firstPage;
                $(e.target).val(state.firstPage == 0 ? state.firstPage + 1 : state.firstPage);
            } else if (state.lastPage <= pageIndex) {
                pageIndex = state.lastPage;
                $(e.target).val(state.firstPage == 0 ? state.lastPage + 1 : state.lastPage);
            }

            if (state.currentPage !== pageIndex) {
                collection.getPage(pageIndex);
            }
        },

        /**
         * Internal method to create a list of page handle objects for the template
         * to render them.
         *
         * @return Array.<Object> an array of page handle objects hashes
         */
        makeHandles: function() {
            var handles = [];

            handles.push({
                type: 'input'
            });

            return PaginationInput.__super__.makeHandles.call(this, handles);
        },
        /**
         * Render pagination view and add validation for input with positive integer value
         */
        render: function() {
            PaginationInput.__super__.render.apply(this, arguments);
            this.$('input').numeric({decimal: false, negative: false});
            return this;
        }
    });

    return PaginationInput;
});
