define(function(require) {
    'use strict';

    var WidgetContainerView;
    var _ = require('underscore');
    var $ = require('jquery');
    var constants = require('orosidebar/js/sidebar-constants');
    var IconView = require('orosidebar/js/app/views/sidebar-widget-container/widget-container-icon-view');
    var BaseView = require('oroui/js/app/views/base/view');

    WidgetContainerView = BaseView.extend({
        template: require('tpl!orosidebar/templates/sidebar-widget-container/widget-container.html'),

        className: function() {
            var classes = ['sidebar-widget', this.model.get('cssClass')];
            return _.compact(classes).join(' ');
        },

        attributes: function() {
            return {
                'data-cid': this.model.cid
            };
        },

        events: {
            'click [data-role="sidebar-widget-toggle"]': 'onClickToggle',
            'click [data-role="sidebar-widget-refresh"]': 'onClickRefresh',
            'click [data-role="sidebar-widget-configure"]': 'onClickSettings',
            'click [data-role="sidebar-widget-remove"]': 'onClickRemove',
            'click [data-role="sidebar-widget-close"]': 'onClickClose',
            'click [data-role="sidebar-widget-icon"]': 'onClickIcon'
        },

        listen: {
            'change model': 'onModelChange',
            'change:state model': 'updateState',
            'change:highlighted model': 'updateHighlight',
            'start-loading model': 'onLoadingStart',
            'end-loading model': 'onLoadingEnd',

            'layout:reposition mediator': 'adjustMaxHeight',
            'widget_dialog:open mediator': 'onWidgetDialogOpen'
        },

        /**
         * @inheritDoc
         */
        constructor: function WidgetContainerView(options) {
            WidgetContainerView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            if (this.subviews.length) {
                // detach all views' elements from DOM before element re-render
                this.subviews.forEach(function(view) {
                    view.$el.detach();
                });
            }

            WidgetContainerView.__super__.render.call(this);
            this.updateHighlight();
            this.updateState();

            if (!this.subviews.length) {
                this.initSubviews();
            } else {
                this.subviews.forEach(function(view) {
                    var viewRole = view.$el.data('role');
                    this.$('[data-role="' + viewRole + '"]').replaceWith(view.$el);
                }.bind(this));
            }

            return this;
        },

        initSubviews: function() {
            var contentView = new this.model.module.ContentView({
                autoRender: true,
                model: this.model,
                el: this.$('[data-role="sidebar-widget-content"]')
            });
            this.subview('contentView', contentView);

            var widgetIconView = new IconView({
                autoRender: true,
                model: this.model,
                el: this.$('[data-role="sidebar-widget-icon"]')
            });
            this.subview('widgetIconView', widgetIconView);

            var headerIconView = new IconView({
                autoRender: true,
                model: this.model,
                el: this.$('[data-role="sidebar-widget-header-icon"]')
            });
            this.subview('headerIconView', headerIconView);
        },

        onModelChange: function(model) {
            var ignoreAttrs = ['highlighted', 'iconClass', 'icon', 'itemsCounter', 'state', 'position'];
            var changedAttrs = _.keys(model.changedAttributes());
            if (_.difference(changedAttrs, ignoreAttrs).length) {
                this.render();
            }
        },

        updateState: function() {
            var isPoppedUp = this.model.get('state') === constants.WIDGET_MAXIMIZED_HOVER;
            var isExpanded = isPoppedUp || this.model.get('state') === constants.WIDGET_MAXIMIZED;
            this.$el.toggleClass('poppedup', isPoppedUp);
            this.$el.toggleClass('expanded', isExpanded);
        },

        updateHighlight: function() {
            this.$el.toggleClass('highlight', this.model.get('highlighted'));
        },

        getTemplateData: function() {
            var data = WidgetContainerView.__super__.getTemplateData.call(this);
            if (this.model.module.titleTemplate) {
                data.title = this.model.module.titleTemplate(data);
            }
            return data;
        },

        adjustMaxHeight: function() {
            var rect;
            var contentMargin;
            var $content;
            var windowHeight;
            var contentView = this.subview('contentView');
            if (contentView) {
                $content = contentView.$el;
                windowHeight = $('html').height();
                if (this.model.get('state') === constants.WIDGET_MAXIMIZED_HOVER) {
                    rect = $content[0].getBoundingClientRect();
                    contentMargin = $content.outerHeight(true) - rect.height;
                    $content.css('max-height', windowHeight - rect.top - contentMargin + 'px');
                } else {
                    $content.css('max-height', 'none');
                }
            }
        },

        onClickToggle: function(e) {
            e.stopPropagation();
            this.model.toggleState();
            this.model.save();
        },

        onClickRefresh: function(e) {
            e.preventDefault();
            var contentView = this.subview('contentView');
            if (contentView) {
                contentView.trigger('refresh');
            }
        },

        onClickSettings: function(e) {
            e.preventDefault();
            this.trigger('setupWidget', this);
        },

        onClickRemove: function(e) {
            e.preventDefault();
            this.trigger('removeWidget', this);
        },

        onClickClose: function(e) {
            e.preventDefault();
            this.model.removeHoverState();
        },

        onClickIcon: function(e) {
            e.stopPropagation();
            if (this.model.isDragged) {
                return;
            }
            this.model.toggleHoverState();
            this.adjustMaxHeight();
        },

        onWidgetDialogOpen: function() {
            this.model.removeHoverState();
        },

        onLoadingStart: function() {
            this.$('[data-role="sidebar-widget-header-icon"]').addClass('loading');
        },

        onLoadingEnd: function() {
            this.$('[data-role="sidebar-widget-header-icon"]').removeClass('loading');
            this.adjustMaxHeight();
        }
    });

    return WidgetContainerView;
});
