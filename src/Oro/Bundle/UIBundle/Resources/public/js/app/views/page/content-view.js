/*global define*/
define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    'oroui/js/tools',
    './../base/page-region-view'
], function ($, _, mediator, tools, PageRegionView) {
    'use strict';

    var PageContentView;

    /**
     * Finds first container that has active scrollbar and sets focus on it for ability of scrolling it by keyboard
     */
    function focusScrollElement() {
        var target,
            scrollable = [
            '.scrollable-container',
            '.other-scroll',
            '.layout-content .scrollable-container',
            '.system-configuration-container .scrollable-container',
            '.scrollspy'
        ];

        target = _.find(scrollable, function (item) {
            var $el = $(item).first(),
                overflow = $el.css('overflow-y');
            return $el.length && /auto|scroll/.test(overflow) && $el[0].scrollHeight > $el[0].clientHeight;
        });

        if(!_.isUndefined(target)) {
            $(target).attr({
                'tabindex': 0,
                'data-scroll-focus': ''
            }).one('blur', function(){
                $(this).removeAttr('data-scroll-focus tabindex');
            }).focus();
        }
    }

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
            // should not be applied before layouting (see init-layout.js)
            // that will give issues on extra small screens
            _.defer(_.bind(this.initFocus, this));

            // force to redraw page header to avoid wrong width
            this.$(".page-title:first").hide().show(0);
        },

        /**
         * Sets focus on first form field
         */
        initFocus: function () {
            var activeElement = document.activeElement,
                delay = 200;

            this.$('form:first').focusFirstInput();

            if(!tools.isMobile() && activeElement === document.activeElement){
                _.delay(focusScrollElement, delay);
            }
        }
    });

    return PageContentView;
});
