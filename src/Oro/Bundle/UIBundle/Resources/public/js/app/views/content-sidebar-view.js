define(function(require) {
    'use strict';

    var ContentSidebarView;
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var BaseView = require('oroui/js/app/views/base/view');
    var layoutHelper = require('oroui/js/tools/layout-helper');
    var mediator = require('oroui/js/mediator');
    var ResizableAreaPlugin = require('oroui/js/app/plugins/plugin-resizable-area');
    var PluginManager = require('oroui/js/app/plugins/plugin-manager');
    var config = require('module').config();

    config = _.extend({
        autoRender: true,
        fixSidebarHeight: true,
        sidebar: '[data-role="sidebar"]',
        scrollbar: '[data-role="sidebar"]',
        content: '[data-role="content"]',
        destroyResizable: false
    }, config);

    ContentSidebarView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'autoRender',
            'fixSidebarHeight',
            'sidebar',
            'scrollbar',
            'content'
        ]),

        autoRender: config.autoRender,

        fixSidebarHeight: config.fixSidebarHeight,

        sidebar: config.sidebar,

        scrollbar: config.scrollbar,

        content: config.content,

        events: {
            'click [data-role="sidebar-minimize"]': 'minimize',
            'click [data-role="sidebar-maximize"]': 'maximize'
        },

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.pluginManager = new PluginManager(this);
            this.pluginManager.create(ResizableAreaPlugin, {
                $resizableEl: this.sidebar,
                $extraEl: this.content
            });

            ContentSidebarView.__super__.initialize.call(this, arguments);
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
            this._toggle('off');
        },

        maximize: function() {
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

            this.pluginManager[show ? 'enable' : 'disable'](ResizableAreaPlugin);
            mediator.execute('changeUrlParam', 'sidebar', show ? null : state);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            this.pluginManager.dispose();

            ContentSidebarView.__super__.dispose.call(this);
        }
    });

    return ContentSidebarView;
});
