define(function(require) {
    'use strict';

    var PageCenterTitleView;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    PageCenterTitleView = BaseView.extend({
        leftBlock: null,

        rightBlock: null,

        listen: {
            'layout:reposition mediator': 'onLayoutReposition'
        },

        /**
         * @inheritDoc
         */
        constructor: function PageCenterTitleView() {
            PageCenterTitleView.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            PageCenterTitleView.__super__.initialize.apply(this, arguments);
            this.$el.siblings().each(_.bind(function(index, el) {
                if ($(el).css('float') === 'left' && !this.leftBlock) {
                    this.leftBlock = el;
                } else if ($(el).css('float') === 'right' && !this.rightBlock) {
                    this.rightBlock = el;
                }
            }, this));
        },

        onLayoutReposition: function() {
            if (!this.leftBlock || !this.rightBlock) {
                return;
            }
            var leftBlockRect = this.leftBlock.getBoundingClientRect();
            var rightBlockRect = this.rightBlock.getBoundingClientRect();
            if (leftBlockRect.bottom <= rightBlockRect.top && leftBlockRect.width > rightBlockRect.width) {
                this.$el.addClass('under-left-block');
            } else {
                this.$el.removeClass('under-left-block');
            }
        }
    });

    return PageCenterTitleView;
});
