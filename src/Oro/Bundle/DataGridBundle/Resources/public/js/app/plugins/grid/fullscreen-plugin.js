define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BasePlugin = require('oroui/js/app/plugins/base/plugin');
    const mediator = require('oroui/js/mediator');
    const tools = require('oroui/js/tools');
    const FloatingHeaderPlugin = require('orodatagrid/js/app/plugins/grid/floating-header-plugin');
    const scrollHelper = require('oroui/js/tools/scroll-helper');

    const FullScreenPlugin = BasePlugin.extend({
        enable: function() {
            this.listenTo(this.main, 'shown rendered content:update', this.updateLayout, this);
            if (this.main.filterManager) {
                this.listenFilterManager();
            } else {
                this.listenTo(this.main, 'filterManager:connected', this.listenFilterManager, this);
            }
            this.listenTo(mediator, 'layout:reposition', this.updateLayout, this);
            this.updateLayout();
            FullScreenPlugin.__super__.enable.call(this);
        },

        disable: function() {
            clearTimeout(this.updateLayoutTimeoutId);
            this.setLayout('default');
            FullScreenPlugin.__super__.disable.call(this);
        },

        listenFilterManager: function() {
            const debouncedLayoutUpdate = _.debounce(this.updateLayout.bind(this), 10);
            this.listenTo(this.main.filterManager, 'afterUpdateList', debouncedLayoutUpdate);
            this.listenTo(this.main.filterManager, 'updateFilter', debouncedLayoutUpdate);
        },

        /**
         * Returns css expression for fullscreen layout
         * @returns {string}
         */
        getCssHeightCalcExpression: function() {
            const documentHeight = scrollHelper.documentHeight();
            const availableHeight = mediator.execute('layout:getAvailableHeight',
                this.main.$grid.parents('.grid-scrollable-container:first'));
            return 'calc(100vh - ' + (documentHeight - availableHeight) + 'px)';
        },

        /**
         * Chooses layout on resize or during creation
         */
        updateLayout: function() {
            if (!this.main.shown) {
                // not ready to apply layout
                // try to do that at next js cycle1
                clearTimeout(this.updateLayoutTimeoutId);
                this.updateLayoutTimeoutId = _.delay(this.updateLayout.bind(this), 0);
                return;
            } else {
                clearTimeout(this.updateLayoutTimeoutId);
            }
            if (tools.isMobile()) {
                this.manager.enable(FloatingHeaderPlugin);
            }
            const layout = mediator.execute('layout:getPreferredLayout', this.main.$grid);
            this.setLayout(layout);
        },

        /**
         * Sets layout and perform all required operations
         *
         * @param newLayout
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
                    mediator.execute('layout:disablePageScroll', this.main.$el);
                    this.main.$grid.parents('.grid-scrollable-container').css({
                        maxHeight: this.getCssHeightCalcExpression()
                    });
                    this.manager.enable(FloatingHeaderPlugin);
                    break;
                case 'scroll':
                case 'default':
                    mediator.execute('layout:enablePageScroll');
                    this.main.$grid.parents('.grid-scrollable-container').css({
                        maxHeight: ''
                    });
                    this.manager.disable(FloatingHeaderPlugin);
                    break;
                default:
                    throw new Error('Unknown grid layout');
            }
            this.main.trigger('layout:update');
        }
    });

    return FullScreenPlugin;
});
