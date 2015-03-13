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
         * Sets focus on first form field
         */
        focusFirstInput: function () {
            this.$('form:first').focusFirstInput();
        }
    });

    return PageContentView;
});
