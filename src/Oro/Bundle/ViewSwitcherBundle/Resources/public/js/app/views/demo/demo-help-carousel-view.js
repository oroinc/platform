/* global window */
define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('text-loader!oroviewswitcher/templates/demo-help-carousel.html');
    require('slick');
    const slides = [];

    const COOKIE_KEY = 'demo_help_carousel_hidden';
    const COOKIE_VALUE = '1';

    const DemoHelpCarouselView = BaseView.extend({
        /**
         * @inheritdoc
         */
        autoRender: true,

        className: 'help-carousel',

        /**
         * @inheritdoc
         */
        template: template,

        visibleClass: 'shown',

        /**
         * Two days expired period
         */
        expiredDelay: 1000 * 60 * 60 * 48,

        /**
         * @inheritdoc
         */
        events: {
            'click [data-role="close"]': 'onClose'
        },

        listen: {
            'demo-page-action:open-help-carousel mediator': 'open'
        },

        /**
         * @inheritdoc
         */
        constructor: function DemoHelpCarouselView(options) {
            DemoHelpCarouselView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.close();

            DemoHelpCarouselView.__super__.dispose.call(this);
        },

        onClose: function(e) {
            e.preventDefault();

            this.close();
            this.setCookie();
        },

        onKeyDown: function(event) {
            if (event.keyCode === 27) {
                this.close();
            }
        },

        /**
         * @inheritdoc
         */
        render: function() {
            DemoHelpCarouselView.__super__.render.call(this);

            const template = _.template(_.pluck(slides, 'content').join(''));

            this.$('[data-role="slides-container"]')
                .html(template(this.getTemplateData()));
        },

        open: function() {
            if (this.$el.hasClass(this.visibleClass)) {
                return;
            }

            this.$el.addClass(this.visibleClass);

            $(window.document).on('keydown' + this.eventNamespace(), this.onKeyDown.bind(this));

            const $carousel = this.getCarouselElement();
            _.defer(function() {
                $carousel.slick({
                    dots: true,
                    infinite: false,
                    speed: 300,
                    slidesToShow: 1,
                    adaptiveHeight: true,
                    autoplay: true,
                    autoplaySpeed: 10000,
                    prevArrow: _.template(
                        `<button type="button" aria-label="<%- _.__('Previous') %>" class="slick-prev">
                            <%- _.__('Previous') %>
                        </button>`
                    )(),
                    nextArrow: _.template(
                        `<button type="button" aria-label="<%- _.__('Next') %>" class="slick-next"">
                            <%- _.__('Next') %>
                        </button>`
                    )()
                });
            });
        },

        openIfApplicable: function() {
            if (DemoHelpCarouselView.isApplicable()) {
                this.open();
            }
        },

        close: function() {
            this.$el.removeClass(this.visibleClass);

            const $carousel = this.getCarouselElement();
            if ($carousel.is('.slick-initialized')) {
                $carousel.slick('unslick');
            }

            $(window.document).off('keydown' + this.eventNamespace());
        },

        getCarouselElement: function() {
            return this.$('[data-role="slides-container"]');
        },

        setCookie: function() {
            if (!navigator.cookieEnabled) {
                return;
            }

            document.cookie = COOKIE_KEY + '=' + COOKIE_VALUE + '; path=/';
        }
    }, {
        /**
         * @static
         * @returns {boolean}
         */
        isApplicable: function() {
            return document.cookie.indexOf(COOKIE_KEY + '=' + COOKIE_VALUE) === -1;
        },

        addSlide: function(order, content) {
            const newSlide = {order: Number(order), content: content};
            const index = _.findIndex(slides, function(slide) {
                return slide.order > newSlide.order;
            });

            if (index === -1) {
                slides.push(newSlide);
            } else {
                slides.splice(index, 0, newSlide);
            }
        }
    });

    return DemoHelpCarouselView;
});
