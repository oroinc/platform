define(function(require) {
    'use strict';

    var LayoutSubtreeView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var LayoutSubtreeManager = require('oroui/js/layout-subtree-manager');
    var $ = require('jquery');

    LayoutSubtreeView = BaseView.extend({
        options: {
            url: window.location.href,
            rootId: '',
            method: 'get',
            reloadEvents: [],
            showLoading: true
        },

        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);
            LayoutSubtreeView.__super__.initialize.apply(this, arguments);
        },

        getOptions: function() {
            return this.options;
        },

        delegateEvents: function() {
            var result = LayoutSubtreeView.__super__.delegateEvents.apply(this, arguments);
            LayoutSubtreeManager.addLayoutSubtreeInstance(this.options);
            mediator.on('layout_subtree_reload_start', this._showLoading, this);
            mediator.on('layout_subtree_reload_done', this._onContentLoad, this);
            mediator.on('layout_subtree_reload_fail', this._hideLoading, this);
            return result;
        },

        undelegateEvents: function() {
            LayoutSubtreeManager.removeLayoutSubtreeInstance(this.options);
            mediator.off('layout_subtree_reload_start', this._showLoading, this);
            mediator.off('layout_subtree_reload_done', this._onContentLoad, this);
            mediator.off('layout_subtree_reload_fail', this._hideLoading, this);
            return LayoutSubtreeView.__super__.undelegateEvents.apply(this, arguments);
        },

        _onContentLoad: function(content) {
            this._hideLoading();
            if (content.hasOwnProperty(this.options.rootId)) {
                content = content[this.options.rootId];
                this.$el.html($(content).children());

                this.disposePageComponents();
                this.initLayout().done(function() {
                });
            }
        },

        _showLoading: function() {
            if (!this.options.showLoading) {
                return;
            }
            this.subview('loadingMask', new LoadingMaskView({
                container: this.$el
            }));
            this.subview('loadingMask').show();
        },

        _hideLoading: function() {
            if (!this.options.showLoading) {
                return;
            }
            this.removeSubview('loadingMask');
        }
    });

    return LayoutSubtreeView;
});