define(function(require) {
    'use strict';

    var LayoutSubtreeView;
    var BaseView = require('oroui/js/app/views/base/view');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var LayoutSubtreeManager = require('oroui/js/layout-subtree-manager');
    var $ = require('jquery');

    LayoutSubtreeView = BaseView.extend({
        options: {
            blockId: '',
            reloadEvents: [],
            showLoading: true
        },

        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);
            LayoutSubtreeView.__super__.initialize.apply(this, arguments);
            LayoutSubtreeManager.addView(this);
            this.initLayout();
        },

        dispose: function() {
            LayoutSubtreeManager.removeView(this);
            return LayoutSubtreeView.__super__.dispose.apply(this, arguments);
        },

        setContent: function(content) {
            this._hideLoading();
            this.$el.html($(content).children());

            this.disposePageComponents();
            this.initLayout();
        },

        beforeContentLoading: function() {
            this._showLoading();
        },

        contentLoadingFail: function() {
            this._hideLoading();
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
