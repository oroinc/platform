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
        scrollbar: '[data-role="sidebar-content"]',
        content: '[data-role="content"]',
        resizableSidebar: !tools.isMobile()
    }, config);

    ContentSidebarView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'autoRender',
            'fixSidebarHeight',
            'sidebar',
            'scrollbar',
            'content',
            'resizableSidebar'
        ]),

        autoRender: config.autoRender,

        fixSidebarHeight: config.fixSidebarHeight,

        sidebar: config.sidebar,

        scrollbar: config.scrollbar,

        content: config.content,

        resizableSidebar: config.resizableSidebar,

        events: {
            'click [data-role="sidebar-minimize"]': 'minimize',
            'click [data-role="sidebar-maximize"]': 'maximize'
        },

        /**
         * @inheritDoc
         */
        constructor: function ContentSidebarView() {
            ContentSidebarView.__super__.constructor.apply(this, arguments);
        },

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            if (this.resizableSidebar) {
                this.initResizableSidebar();
            }
            ContentSidebarView.__super__.initialize.call(this, arguments);

            mediator.on('swipe-action-left', this.minimize, this);
            mediator.on('swipe-action-right', this.maximize, this);
        },

        /**
         * {@inheritDoc}
         */
        render: function() {
            if (this.fixSidebarHeight && !tools.isMobile()) {
                layoutHelper.setAvailableHeight(this.scrollbar, this.sidebar);
            }

            this._toggle(this.getSidebarState());

            ContentSidebarView.__super__.render.apply(this, arguments);
        },

        initResizableSidebar: function() {
            this.pluginManager = new PluginManager(this);
            this.pluginManager.create(ResizableAreaPlugin, {
                $resizableEl: this.sidebar,
                resizableOptions: {
                    resize: _.bind(this._resize, this),
                    create: _.bind(this._create, this)
                }
            });
        },

        getSidebarState: function() {
            return tools.unpackFromQueryString(location.search).sidebar || 'on';
        },

        minimize: function() {
            this._toggle('off');
        },

        maximize: function() {
            this._toggle('on');
        },

        _create: function() {
            this.$(this.content).css({
                width: 'calc(100% - ' + this.$(this.sidebar).outerWidth() + 'px)'
            });
        },

        _resize: function(event, ui) {
            this.$(this.content).css({
                width: 'calc(100% - ' + ui.size.width + 'px)'
            });
        },

        /**
         * @private
         * @param {String} state
         */
        _toggle: function(state) {
            var show = state === 'on';

            if (this.resizableSidebar) {
                if (!show) {
                    this.pluginManager.getInstance(ResizableAreaPlugin).removePreviousState();
                    this.$(this.content).css({
                        width: ''
                    });
                }
                this.pluginManager[show ? 'enable' : 'disable'](ResizableAreaPlugin);
            }

            if (!this.resizableSidebar && !show) {
                this.$(this.sidebar).css({
                    width: ''
                });
            }

            this.$(this.sidebar).toggleClass('content-sidebar-minimized', !show);
            mediator.execute('changeUrlParam', 'sidebar', show ? null : state);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.pluginManager) {
                this.pluginManager.dispose();
            }

            ContentSidebarView.__super__.dispose.call(this);
        }
    });

    return ContentSidebarView;
});
