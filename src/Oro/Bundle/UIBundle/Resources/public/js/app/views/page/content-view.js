/*global define*/
define([
    'oroui/js/mediator',
    './../base/page-region-view'
], function (mediator, PageRegionView) {
    'use strict';

    var PageContentView;

    PageContentView = PageRegionView.extend({
        template: function (data) {
            return data.content;
        },
        pageItems: ['content', 'scripts'],

        listen: {
            'page:afterChange mediator': 'onPageAfterChange'
        },

        events: {
            'click .add-list-item': 'onAddListItem',
            'click .removeRow': 'onRemoveListItem'
        },

        render: function () {
            var data;
            PageContentView.__super__.render.call(this);

            // @TODO discuss if scripts section is still in use
            data = this.getTemplateData();
            if (data && data.scripts) {
                this.$el.append(data.scripts);
            }

            return this;
        },

        /**
         * Handles page:afterChange event
         */
        onPageAfterChange: function () {
            this.focusFirstInput();
        },

        /**
         * Handles click on add list button
         *  - fetches template of list item
         *  - update the index
         *  - add the item to list container
         *
         * @param {jQuery.Event} e
         */
        onAddListItem: function (e) {
            e.preventDefault();
            var $listContainer, index, html;

            $listContainer = this.$(e.currentTarget).siblings('.collection-fields-list');
            index = $listContainer.data('last-index') || $listContainer.children().length;
            html = $listContainer.attr('data-prototype').replace(/__name__/g, index);
            $listContainer.append(html).data('last-index', index + 1);

            // initialize components in view's markup
            mediator.execute('layout:init', $listContainer, this);
        },

        /**
         * Handles click on remove list button
         *  - removes the item from list container
         *
         * @param {jQuery.Event} e
         */
        onRemoveListItem: function (e) {
            e.preventDefault();
            this.$(e.currentTarget).closest('*[data-content]').remove();
        },

        /**
         * Sets focus on first form field
         */
        focusFirstInput: function () {
            this.$('form:first').focusFirstInput();
        }
    });

    return PageContentView;
});
