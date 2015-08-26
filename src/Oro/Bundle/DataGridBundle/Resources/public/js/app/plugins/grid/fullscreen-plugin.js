define(function(require) {
    'use strict';

    var FullScreenPlugin;
    var $ = require('jquery');
    var _ = require('underscore');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');
    var FloatingHeaderPlugin = require('orodatagrid/js/app/plugins/grid/floating-header-plugin');

    FullScreenPlugin = BasePlugin.extend({
        enable: function() {
            this.listenTo(this.main, 'shown', this.updateLayout, this);
            this.listenTo(this.main, 'rendered', this.updateLayout, this);
            this.listenTo(mediator, 'layout:reposition', this.updateLayout, this);
            this.updateLayout();
            FullScreenPlugin.__super__.enable.call(this);
        },

        disable: function() {
            clearTimeout(this.updateLayoutTimeoutId);
            this.setLayout('default');
            FullScreenPlugin.__super__.disable.call(this);
        },

        /**
         * Returns css expression for fullscreen layout
         * @returns {string}
         */
        getCssHeightCalcExpression: function() {
            var documentHeight = $(document).height();
            var availableHeight = mediator.execute('layout:getAvailableHeight',
                    this.main.$grid.parents('.grid-scrollable-container:first'));
            return 'calc(100vh - ' + (documentHeight - availableHeight) + 'px)';
        },

        /**
         * Chooses layout on resize or during creation
         */
        updateLayout: function() {
            var layout;
            if (!this.main.rendered || !this.main.$grid.parents('body').length || !this.main.$el.is(':visible')) {
                // not ready to apply layout
                // try to do that at next js cycle1
                clearTimeout(this.updateLayoutTimeoutId);
                this.updateLayoutTimeoutId = _.delay(_.bind(this.updateLayout, this), 50);
                return;
            } else {
                clearTimeout(this.updateLayoutTimeoutId);
            }
            if (tools.isMobile()) {
                this.manager.enable(FloatingHeaderPlugin);
            }
            layout = mediator.execute('layout:getPreferredLayout', this.main.$grid);
            this.setLayout(layout);
        },

        /**
         * Sets layout and perform all required operations
         */
        setLayout: function(newLayout) {
            if (newLayout === this.main.layout) {
                if (newLayout === 'fullscreen') {
                    this.main.$grid.parents('.grid-scrollable-container').css({
                        maxHeight: this.getCssHeightCalcExpression()
                    });
                    this.main.trigger('layout:update');
                }
                return;
            }
            this.main.layout = newLayout;
            switch (newLayout) {
                case 'fullscreen':
                    this.manager.enable(FloatingHeaderPlugin);
                    this.main.$grid.parents('.grid-scrollable-container').css({
                        maxHeight: this.getCssHeightCalcExpression()
                    });
                    mediator.execute('layout:disablePageScroll', this.main.$el);
                    break;
                case 'scroll':
                case 'default':
                    this.manager.disable(FloatingHeaderPlugin);
                    this.main.$grid.parents('.grid-scrollable-container').css({
                        maxHeight: ''
                    });
                    mediator.execute('layout:enablePageScroll');
                    break;
                default:
                    throw new Error('Unknown grid layout');
            }
            this.main.trigger('layout:update');
        }
    });

    return FullScreenPlugin;
});
