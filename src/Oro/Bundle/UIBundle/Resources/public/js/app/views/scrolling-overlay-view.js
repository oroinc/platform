define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');

    const ENTER_KEY_CODE = 13;
    const SPACE_KEY_CODE = 32;
    const SCROLL_STEP = 40;

    const ScrollingOverlayView = BaseView.extend({
        /**
         * @inheritdoc
         */
        events: function() {
            const events = {};

            events['keydown ' + this._scrollButtonsSelector] = this.onScroll;
            events['keyup ' + this._scrollButtonsSelector] = this.stopScroll;
            events['mousedown ' + this._scrollButtonsSelector] = this.onScroll;
            events['mouseup ' + this._scrollButtonsSelector] = this.stopScroll;
            events['mouseout ' + this._scrollButtonsSelector] = this.stopScroll;
            return events;
        },

        /**
         * @inheritdoc
         */
        listen: {
            'layout:reposition mediator': 'onLayoutReposition'
        },

        className: 'scrolling-overlay',

        buttonScrollClassName: '',

        invisibleClassName: 'invisible',

        timeout: 50,

        timerId: null,

        /**
         * {jQuery.Element}
         */
        $scrollingContent: null,

        scrollStep: SCROLL_STEP,

        /**
         * @inheritdoc
         */
        constructor: function ScrollingOverlayView(options) {
            this._uniqKey = _.uniqueId('scrolling-overlay-');
            this._scrollButtonsSelector = '[id^=' + this._uniqKey + ']';

            ScrollingOverlayView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.defaults(
                _.pick(options, ['buttonScrollClassName', '$scrollingContent', 'scrollStep']), this));

            this.$scrollingContent = $(this.$scrollingContent);

            if (!this.$scrollingContent.length) {
                throw new Error('Required option `$scrollingContent` is missing in `ScrollingOverlayView`');
            }

            ScrollingOverlayView.__super__.initialize.call(this, options);
        },

        setScrollingContent($scrollingContents) {
            if (this.$scrollingContent.length) {
                this.$scrollingContent.off(this.eventNamespace());
                this.$scrollingContent
                    .removeClass('scrolling-trigger-content')
                    .unwrap();
            }

            this.$scrollingContent = $($scrollingContents);
            this.render();
        },

        /**
         * @inheritdoc
         */
        render: function() {
            ScrollingOverlayView.__super__.render.call(this);

            this.removeScrollButtons();
            this.$scrollingContent
                .addClass('scrolling-overlay-content')
                .wrap(this.$el);
            this.$scrollingContent.before(
                $('<button></button>', {
                    'id': this._uniqKey + '-up',
                    'class': 'scrolling-overlay-btn scrolling-overlay-btn--up ' +
                        this.buttonScrollClassName + ' ' + this.invisibleClassName,
                    'data-direction': 'up',
                    'aria-label': _.__('oro.ui.scroll_up_label')
                })
            );
            this.$scrollingContent.after(
                $('<button></button>', {
                    'id': this._uniqKey + '-down',
                    'class': 'scrolling-overlay-btn scrolling-overlay-btn--down ' +
                        this.buttonScrollClassName + ' ' + this.invisibleClassName,
                    'data-direction': 'down',
                    'aria-label': _.__('oro.ui.scroll_down_label')
                })
            );

            this.updateScrollButtons();

            this.$scrollingContent.off(this.eventNamespace());
            this.$scrollingContent.on('scroll' + this.eventNamespace(),
                _.debounce(this.updateScrollButtons.bind(this), this.timeout));

            return this;
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$scrollingContent
                .removeClass('scrolling-trigger-content')
                .unwrap();
            this.removeScrollButtons();

            ScrollingOverlayView.__super__.dispose.call(this);
        },

        onLayoutReposition: function() {
            this.updateScrollButtons();
        },

        /**
         * @param {number} value
         */
        setScrollStep: function(value) {
            if (this.disposed || !_.isNumber(value)) {
                return;
            }

            this.scrollStep = value;
        },

        /**
         * Updating the scrollTop positions the vertical scroll of scrolling content
         * @param {string} direction='up'|'down'
         */
        updateVerticalScroll: function(direction) {
            let newScrollPosition = this.$scrollingContent.scrollTop();

            if (direction === 'up') {
                newScrollPosition -= this.scrollStep;
            } else if (direction === 'down') {
                newScrollPosition += this.scrollStep;
            }

            this.$scrollingContent.scrollTop(newScrollPosition);
            this.updateScrollButtons();
        },

        onScroll: function(event) {
            if ([undefined, ENTER_KEY_CODE, SPACE_KEY_CODE].indexOf(event.keyCode) === -1) {
                return;
            }

            const direction = $(event.currentTarget).data('direction');

            this.updateVerticalScroll(direction);
            this.timerId = setInterval(this.updateVerticalScroll.bind(this, direction), 150);
        },

        /**
         * Undo scroll
         */
        stopScroll: function() {
            clearInterval(this.timerId);
        },

        /**
         * Show / hide scroll handles
         */
        updateScrollButtons: function() {
            const $scrollHandles = $(this._scrollButtonsSelector);
            const scrollContentHeight = Math.round(this.$scrollingContent.outerHeight());
            const scrollTop = this.$scrollingContent.scrollTop();
            const bottomPosition = _.reduce(this.$scrollingContent.children(), function(result, item) {
                return result + Math.round($(item).outerHeight());
            }, 0);

            if (scrollContentHeight >= bottomPosition) {
                $scrollHandles.addClass(this.invisibleClassName);
                return;
            }

            $scrollHandles
                .filter('[data-direction="up"]')
                .toggleClass(this.invisibleClassName,
                    scrollTop === 0
                );

            $scrollHandles
                .filter('[data-direction="down"]')
                .toggleClass(
                    this.invisibleClassName,
                    scrollTop >= bottomPosition - scrollContentHeight
                );
        },

        removeScrollButtons: function() {
            $(this._scrollButtonsSelector).remove();
        }
    });

    return ScrollingOverlayView;
});
