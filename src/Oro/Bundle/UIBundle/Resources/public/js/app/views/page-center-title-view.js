define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const PageCenterTitleView = BaseView.extend({
        leftBlock: null,

        rightBlock: null,

        currentClass: '',

        listen: {
            'layout:reposition mediator': 'onLayoutReposition'
        },

        /**
         * @inheritdoc
         */
        constructor: function PageCenterTitleView(options) {
            PageCenterTitleView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            PageCenterTitleView.__super__.initialize.call(this, options);

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
                const storedDisplay = this.el.style.display;
                this.el.style.display = 'none';
                this.currentClass = this.inFewRows() ? 'center-under-left' : 'center-under-both';
                this.container.classList.add(this.currentClass);
                this.el.style.display = storedDisplay;
            }
        },

        inFewRows: function() {
            const leftRect = this.leftBlock.getBoundingClientRect();
            const rightRect = this.rightBlock.getBoundingClientRect();

            return leftRect.bottom <= rightRect.top || rightRect.bottom <= leftRect.top;
        }
    });

    return PageCenterTitleView;
});
