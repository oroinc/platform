/*global define*/
define(function (require) {
    'use strict';

    var PaginationView,
        __ = require('orotranslation/js/translator'),
        BaseView = require('oroui/js/app/views/base/view'),
        template = require('text!../../../templates/comment/pagination-view.html');

    PaginationView = BaseView.extend({
        autoRender: true,
        template: template,
        attributes: {
            'class': 'pagination pagination-centered'
        },

        events: {
            'change [data-action-name=goto_page]': 'goToPage',
            'click [data-action-name=goto_next]': 'goToNextPage',
            'click [data-action-name=goto_previous]': 'goToPreviousPage'
        },

        listen: {
            'sync collection': 'render'
        },

        getTemplateData: function () {
            var data = {
                current: this.collection.getPage(),
                pages: this.collection.getPagesQuantity(),
                records: this.collection.getRecordsQuantity()
            };
            return data;
        },

        goToPage: function (e) {
            var page;
            e.preventDefault();
            page = parseInt(this.$(e.target).val(), 10);
            this.collection.setPage(page);
        },

        goToNextPage: function (e) {
            var page;
            e.preventDefault();
            page = this.collection.getPage() + 1;
            this.collection.setPage(page);
        },

        goToPreviousPage: function (e) {
            var page;
            e.preventDefault();
            page = this.collection.getPage() - 1;
            this.collection.setPage(page);
        }
    });

    return PaginationView;
});
