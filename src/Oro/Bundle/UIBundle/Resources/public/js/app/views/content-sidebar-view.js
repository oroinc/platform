import $ from 'jquery';
import _ from 'underscore';
import tools from 'oroui/js/tools';
import BaseView from 'oroui/js/app/views/base/view';
import layoutHelper from 'oroui/js/tools/layout-helper';
import mediator from 'oroui/js/mediator';
import ResizableAreaPlugin from 'oroui/js/app/plugins/plugin-resizable-area';
import PluginManager from 'oroui/js/app/plugins/plugin-manager';
import moduleConfig from 'module-config';

const config = {
    autoRender: true,
    fixSidebarHeight: true,
    sidebar: '[data-role="sidebar"]',
    scrollbar: '[data-role="sidebar-content"]',
    content: '[data-role="content"]',
    controls: '[data-role="sidebar-controls"]',
    resizableSidebar: !tools.isMobile(),
    ...moduleConfig(module.id)
};

const ContentSidebarView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'autoRender',
        'fixSidebarHeight',
        'sidebar',
        'scrollbar',
        'content',
        'controls',
        'resizableSidebar'
    ]),

    autoRender: config.autoRender,

    fixSidebarHeight: config.fixSidebarHeight,

    sidebar: config.sidebar,

    scrollbar: config.scrollbar,

    content: config.content,

    controls: config.controls,

    resizableSidebar: config.resizableSidebar,

    events() {
        return {
            'click [data-role="sidebar-minimize"]': 'minimize',
            'click [data-role="sidebar-maximize"]': 'maximize',
            'swipeleft': 'minimize',
            'swiperight': 'maximize'
        };
    },

    /**
     * @inheritdoc
     */
    constructor: function ContentSidebarView(options) {
        ContentSidebarView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        if (this.resizableSidebar) {
            this.initResizableSidebar();
        }
        ContentSidebarView.__super__.initialize.call(this, options);
    },

    /**
     * @inheritdoc
     */
    delegateEvents: function(events) {
        ContentSidebarView.__super__.delegateEvents.call(this, events);
        $(window).on(`scroll${this.eventNamespace()}`, this.onScroll.bind(this));
        return this;
    },

    /**
     * @inheritdoc
     */
    undelegateEvents: function() {
        $(window).off(`scroll${this.eventNamespace()}`);
        return ContentSidebarView.__super__.undelegateEvents.call(this);
    },

    /**
     * @param {Object} e
     */
    onScroll(e) {
        if (this.$linePattern) {
            const bottom = $(this.controls)[0].getBoundingClientRect().bottom;
            const scrollY = window.scrollY;
            let position = bottom;

            if (scrollY === 0) {
            // Property will be removed;
                position = '';
            // Element out of screen;
            } else if (bottom <= 0) {
                position = 0;
            }

            this.$linePattern.css('top', position);
        }
    },

    /**
     * @inheritdoc
     */
    render: function() {
        if (this.fixSidebarHeight && !tools.isMobile()) {
            layoutHelper.setAvailableHeight(this.scrollbar, this.sidebar);
        }

        this._toggle(this.getSidebarState());

        ContentSidebarView.__super__.render.call(this);
    },

    initResizableSidebar: function() {
        this.pluginManager = new PluginManager(this);
        this.pluginManager.create(ResizableAreaPlugin, {
            $resizableEl: this.sidebar
        });
    },

    getSidebarState: function() {
        return tools.unpackFromQueryString(location.search).sidebar || 'on';
    },

    minimize: function(coords, eventTarget, event) {
        if (!event || event.pageX <= this.$(this.sidebar).width()) {
            this._toggle('off');
        }
    },

    maximize: function(coords, eventTarget, event) {
        if (!event || event.pageX <= this.$(this.sidebar).width()) {
            this._toggle('on');
        }
    },

    /**
     * @private
     * @param {String} state
     */
    _toggle: function(state) {
        const show = state === 'on';

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
        this.toggleLinePattern(!show);

        mediator.execute('changeUrlParam', 'sidebar', show ? null : state);
    },

    /**
     * Add or remove sticked block
     * @param [add]
     */
    toggleLinePattern(add) {
        if (!_.isMobile()) {
            return;
        }

        const className = 'line-pattern';

        $(this.controls).find(`.${className}`).remove();
        delete this.$linePattern;

        if (add) {
            this.$linePattern = $(`<div class="${className}"></div>`);
            $(this.controls).append(this.$linePattern);
        }
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.pluginManager) {
            this.pluginManager.dispose();
        }

        this.toggleLinePattern();
        ContentSidebarView.__super__.dispose.call(this);
    }
});

export default ContentSidebarView;
