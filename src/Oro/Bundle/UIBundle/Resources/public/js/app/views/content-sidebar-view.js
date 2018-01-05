define(function(require) {
    'use strict';

    var ContentSidebarView;
    var ResizableAreaView = require('oroui/js/app/views/resizable-area-view');
    var layoutHelper = require('oroui/js/tools/layout-helper');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');
    var _ = require('underscore');
    var config = require('module').config();
    config = _.extend({
        autoRender: true,
        fixSidebarHeight: true,
        sidebar: '[data-role="sidebar"]',
        scrollbar: '[data-role="sidebar"]',
        content: '[data-role="content"]',
        uniqueStorageKey: 'contentSidebarResizableAreaUniq'
    }, config);

    ContentSidebarView = ResizableAreaView.extend({
        optionNames: ResizableAreaView.prototype.optionNames.concat([
            'autoRender',
            'fixSidebarHeight',
            'sidebar',
            'scrollbar',
            'content',
            'uniqueStorageKey'
        ]),

        autoRender: config.autoRender,

        fixSidebarHeight: config.fixSidebarHeight,

        sidebar: config.sidebar,

        scrollbar: config.scrollbar,

        content: config.content,

        uniqueStorageKey: config.uniqueStorageKey,

        events: {
            'click [data-role="sidebar-minimize"]': 'minimize',
            'click [data-role="sidebar-maximize"]': 'maximize'
        },

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            var args = _.defaults(_.clone(options), {
                $resizableEl: this.sidebar,
                $extraEl: this.content,
                uniqueStorageKey: this.uniqueStorageKey
            });

            ContentSidebarView.__super__.initialize.call(this, args);
        },

        /**
         * {@inheritDoc}
         */
        render: function() {
            if (this.fixSidebarHeight && !tools.isMobile()) {
                layoutHelper.setAvailableHeight(this.scrollbar, this.$el);
            }

            var state = tools.unpackFromQueryString(location.search).sidebar || 'on';
            this._toggle(state);

            ContentSidebarView.__super__.render.apply(this, arguments);
        },

        minimize: function() {
            this.disableResizable(true);
            this._toggle('off');
        },

        maximize: function() {
            this.enableResizable(true);
            this._toggle('on');
        },

        /**
         * @private
         * @param {String} state
         */
        _toggle: function(state) {
            var show = state === 'on';

            this.$(this.sidebar).toggleClass('content-sidebar-minimized', !show);
            this.$(this.content).toggleClass('content-sidebar-minimized', !show);

            mediator.execute('changeUrlParam', 'sidebar', show ? null : state);
        }
    });

    return ContentSidebarView;
});
