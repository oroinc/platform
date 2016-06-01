define(function(require) {
    'use strict';

    var LayoutSubtreeView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var Error = require('oroui/js/error');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var $ = require('jquery');
    var _ = require('underscore');

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

        delegateEvents: function() {
            var result = LayoutSubtreeView.__super__.delegateEvents.apply(this, arguments);
            _.each(this.options.reloadEvents || [], function(event) {
                mediator.on(event, this.reloadLayout, this);
            }, this);
            return result;
        },

        undelegateEvents: function() {
            _.each(this.options.reloadEvents || [], function(event) {
                mediator.off(event, this.reloadLayout, this);
            }, this);
            return LayoutSubtreeView.__super__.undelegateEvents.apply(this, arguments);
        },

        reloadLayout: function() {
            this._showLoading();
            $.ajax(this.getLoadingOptions())
                .done(_.bind(this._onContentLoad, this))
                .fail(_.bind(this._onContentLoadFail, this));
        },

        getLoadingOptions: function() {
            return {
                url: this.options.url,
                data: {
                    layout_root_id: this.options.rootId
                },
                type: this.options.method
            };
        },

        _onContentLoadFail: function(jqxhr) {
            this._hideLoading();
            Error.handle({}, jqxhr, {enforce: true});
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
