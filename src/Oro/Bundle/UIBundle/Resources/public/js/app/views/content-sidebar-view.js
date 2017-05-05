define(function(require) {
    'use strict';

    var ContentSidebarView;
    var BaseView = require('oroui/js/app/views/base/view');
    var layoutHelper = require('oroui/js/tools/layout-helper');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');

    ContentSidebarView = BaseView.extend({
        autoRender: true,

        fixSidebarHeight: true,

        sidebar: '[data-role="sidebar"]',

        content: '[data-role="content"]',

        events: {
            'click [data-role="sidebar-minimize"]': 'minimize',
            'click [data-role="sidebar-maximize"]': 'maximize'
        },

        /**
         * {@inheritDoc}
         */
        render: function() {
            if (this.fixSidebarHeight) {
                layoutHelper.setAvailableHeight(this.sidebar, this.$el);
            }

            var state = tools.unpackFromQueryString(location.search).sidebar || 'on';
            this._toggle(state);
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

            this.$(this.sidebar).toggleClass('content-sidebar-maximized', show)
                .toggleClass('content-sidebar-minimized', !show);
            this.$(this.content).toggleClass('content-sidebar-maximized', show)
                .toggleClass('content-sidebar-minimized', !show);

            mediator.execute('changeUrlParam', 'sidebar', show ? null : state);
        }
    });

    return ContentSidebarView;
});
