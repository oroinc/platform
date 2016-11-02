define(function(require) {
    'use strict';

    var LayoutSubtreeView;
    var BaseView = require('oroui/js/app/views/base/view');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var LayoutSubtreeManager = require('oroui/js/layout-subtree-manager');
    var $ = require('jquery');

    LayoutSubtreeView = BaseView.extend({
        options: {
            rootId: '',
            reloadEvents: [],
            showLoading: true
        },

        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);
            LayoutSubtreeView.__super__.initialize.apply(this, arguments);
            LayoutSubtreeManager.addLayoutSubtreeInstance(this);
        },

        dispose: function() {
            LayoutSubtreeManager.removeLayoutSubtreeInstance(this.options.rootId);
            return LayoutSubtreeView.__super__.dispose.apply(this, arguments);
        },

        _onContentLoad: function(content) {
            this._hideLoading();
            this.$el.html($(content).children());

            this.disposePageComponents();
            this.initLayout().done(function() {
            });
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
