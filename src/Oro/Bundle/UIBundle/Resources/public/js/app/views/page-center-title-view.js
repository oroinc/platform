define(function(require) {
    'use strict';

    var PageCenterTitleView;
    var BaseView = require('oroui/js/app/views/base/view');

    PageCenterTitleView = BaseView.extend({
        leftBlock: null,

        rightBlock: null,

        currentClass: '',

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

            this.leftBlock = this.$el.siblings('.pull-left-extra')[0];
            this.rightBlock = this.$el.siblings('.title-buttons-container')[0];
            this.container = this.el.parentNode;
        },

        onLayoutReposition: function() {
            if (!this.leftBlock || !this.rightBlock) {
                return;
            }

            if (this.currentClass) {
                this.container.classList.remove(this.currentClass);
                this.currentClass = '';
            }

            if (this.el.style.display === 'none') {
                return;
            }

            if (this.inFewRows()) {
                var storedDisplay = this.el.style.display;
                this.el.style.display = 'none';
                this.currentClass = this.inFewRows() ? 'center-under-left' : 'center-under-both';
                this.container.classList.add(this.currentClass);
                this.el.style.display = storedDisplay;
            }
        },

        inFewRows: function() {
            var leftRect = this.leftBlock.getBoundingClientRect();
            var rightRect = this.rightBlock.getBoundingClientRect();

            return leftRect.bottom <= rightRect.top || rightRect.bottom <= leftRect.top;
        }
    });

    return PageCenterTitleView;
});
