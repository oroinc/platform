/*global define*/
define([
    'oroui/js/mediator',
    'oroui/js/tools',
    './../base/page-region-view'
], function (mediator, tools, PageRegionView) {
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
            var view = this,
                activeElement = document.activeElement;

            function focusScrollElement() {
                // timeout for fix Mozilla DOM updating latency
                setTimeout(view._focusScrollElement, 200);
            }

            view.$('form:first').focusFirstInput();

            if(!tools.isMobile() && activeElement === document.activeElement){
                if(document.hasOwnProperty('page-rendered')) {
                    focusScrollElement();
                } else {
                    mediator.on('page-rendered', focusScrollElement);
                }
            }

        },

        _focusScrollElement: function() {
            var scrollable = [
                '.scrollable-container',
                '.other-scroll',
                '.layout-content .scrollable-container',
                '.scrollspy'
            ];

            $.each(scrollable, function () {

                var $el = $(this).first(),
                    overflow = $el.css('overflow-y');

                if ($el.length > 0 && /auto|scroll/.test(overflow) && $el[0].scrollHeight > $el[0].clientHeight) {
                    $el.attr('tabindex', 0).css('outline', '0 none').focus();
                    return false;
                }
            });
        }
    });

    return PageContentView;
});
