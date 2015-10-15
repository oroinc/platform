define(function(require) {
    'use strict';

    var WidgetContainerView;
    var _ = require('underscore');
    var Backbone = require('backbone');
    var constants = require('../constants');
    var widgetMinTemplate = require('tpl!./templates/widget-min-template.html');
    var widgetMaxTemplate = require('tpl!./templates/widget-max-template.html');
    var widgetIconTemplate = require('tpl!./templates/icon-template.html');
    var BaseView = require('oroui/js/app/views/base/view');

    WidgetContainerView = BaseView.extend({
        templateMin: widgetMinTemplate,
        templateMax: widgetMaxTemplate,
        templateIcon: widgetIconTemplate,

        events: {
            'click .sidebar-widget-header-toggle': 'onClickToggle',
            'click .sidebar-widget-refresh': 'onClickRefresh',
            'click .sidebar-widget-settings': 'onClickSettings',
            'click .sidebar-widget-remove': 'onClickRemove',
            'click .sidebar-widget-close': 'onClickClose'
        },

        listen: {
            'change model': 'render',
            'start-loading model': 'onLoadingStart',
            'end-loading model': 'onLoadingEnd'
        },

        render: function() {
            this.$el.attr('data-cid', this.model.cid);

            if (this.model.get('cssClass')) {
                this.$el.attr('class', this.model.get('cssClass'));
            }

            if (this.model.get('state') === constants.WIDGET_MAXIMIZED_HOVER) {
                this.$el.addClass('sidebar-widget-popup');
            } else {
                this.$el.addClass('sidebar-widget-embedded');
            }

            this.$el.toggleClass('sidebar-highlight', this.model.get('highlighted'));

            this.model.loadModule().then(_.bind(this._deferredRender, this));

            return this;
        },

        getTemplateFunction: function() {
            var template = this.templateMax;
            if (this.model.get('state') === constants.WIDGET_MINIMIZED) {
                template = this.templateMin;
            }
            return template;
        },

        getTemplateData: function() {
            var data = WidgetContainerView.__super__.getTemplateData.call(this);
            if (this.model.module.titleTemplate) {
                data.title = this.model.module.titleTemplate(data);
            }
            data.icon = this.templateIcon(data);
            return data;
        },

        /**
         * Renders the widget content once widget module is loaded
         *
         * @protected
         */
        _deferredRender: function() {
            WidgetContainerView.__super__.render.call(this);

            if (this.model.get('state') !== constants.WIDGET_MINIMIZED) {
                if (this.contentView) {
                    this.contentView.dispose();
                }
                this.contentView = new this.model.module.ContentView({
                    autoRender: true,
                    model: this.model,
                    el: this.$('.sidebar-widget-content')
                });
            }
        },

        setOffset: function(cord) {
            var view = this;
            view.$el.offset(cord);
        },

        onClickToggle: function(e) {
            e.stopPropagation();
            e.preventDefault();

            this.model.toggleState();
            this.model.save();
        },

        onClickRefresh: function(e) {
            e.stopPropagation();
            e.preventDefault();

            Backbone.trigger('refreshWidget', this.model.cid);
        },

        onClickSettings: function(e) {
            e.stopPropagation();
            e.preventDefault();

            Backbone.trigger('setupWidget', this.model.cid);
        },

        onClickRemove: function(e) {
            e.stopPropagation();
            e.preventDefault();
            Backbone.trigger('removeWidget', this.model.cid);
        },

        onClickClose: function(e) {
            e.stopPropagation();
            e.preventDefault();
            Backbone.trigger('closeWidget', this.model.cid);
        },

        onLoadingStart: function() {
            this.$('.sidebar-widget-header-icon').addClass('loading');
        },

        onLoadingEnd: function() {
            this.$('.sidebar-widget-header-icon').removeClass('loading');
        }
    });

    return WidgetContainerView;
});
