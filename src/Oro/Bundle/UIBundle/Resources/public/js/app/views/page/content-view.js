define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    const PageRegionView = require('./../base/page-region-view');

    /**
     * Finds first container that has active scrollbar and sets focus on it for ability of scrolling it by keyboard
     */
    function focusScrollElement() {
        const scrollable = [
            '.scrollable-container',
            '.other-scroll',
            '.layout-content .scrollable-container',
            '.system-configuration-container .scrollable-container',
            '.scrollspy'
        ];

        const target = _.find(scrollable, function(item) {
            const $el = $(item).first();
            const overflow = $el.css('overflow-y');
            return $el.length && /auto|scroll/.test(overflow) && $el[0].scrollHeight > $el[0].clientHeight;
        });

        if (!_.isUndefined(target)) {
            $(target).attr({
                'tabindex': 0,
                'data-scroll-focus': ''
            }).one('blur', function() {
                $(this).removeAttr('data-scroll-focus tabindex');
            }).focus();
        }
    }

    const PageContentView = PageRegionView.extend({
        /**
         * @inheritdoc
         */
        constructor: function PageContentView(options) {
            PageContentView.__super__.constructor.call(this, options);
        },

        template: function(data) {
            return data.content;
        },
        pageItems: ['content', 'scripts'],

        listen: {
            'page:afterChange mediator': 'onPageAfterChange'
        },

        render: function() {
            PageContentView.__super__.render.call(this);

            const data = this.getTemplateData();

            if (data && data.scripts) {
                this.$el.append(data.scripts);
            }

            if (data) {
                this.initLayout();
            }

            return this;
        },

        /**
         * Handles page:afterChange event
         */
        onPageAfterChange: function() {
            // should not be applied before layouting (see init-layout.js)
            // that will give issues on extra small screens
            _.defer(this.initFocus.bind(this));

            // force to redraw page header to avoid wrong width
            this.$('.page-title:first').hide().show(0);
        },

        /**
         * Sets focus on first form field in case active element
         * is not active on purpose (autofocus attribute)
         */
        initFocus: function() {
            const activeElement = document.activeElement;

            if (!$(activeElement).is('body') || tools.isTouchDevice() || $(activeElement).is('[autofocus]')) {
                return;
            }

            const $form = this.$('form:first');

            if ($form.length) {
                $form.focusFirstInput();
            } else {
                _.delay(focusScrollElement, 200);
            }
        }
    });

    return PageContentView;
});
