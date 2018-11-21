define(function(require) {
    'use strict';

    var SidebarView;

    require('jquery-ui');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');
    var WidgetPickerModal = require('orosidebar/js/app/views/widget-picker-modal-view');
    var WidgetSetupModalView = require('orosidebar/js/app/views/widget-setup-modal-view');
    var constants = require('orosidebar/js/sidebar-constants');
    require('jquery.mCustomScrollbar');
    require('jquery-ui');

    SidebarView = BaseCollectionView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'availableWidgets'
        ]),

        template: require('tpl!orosidebar/templates/sidebar.html'),

        itemView: require('orosidebar/js/app/views/sidebar-widget-container/widget-container-view'),

        listSelector: '[data-role="sidebar-content"]',

        /**
         * mCustomScrollbar initialization options
         * @type {Object}
         */
        mcsOptions: {
            axis: 'y',
            contentTouchScroll: 10,
            scrollInertia: 0,
            documentTouchScroll: true,
            theme: 'inset-dark',
            advanced: {
                autoExpandVerticalScroll: 3,
                updateOnContentResize: true,
                updateOnImageLoad: false
            },
            callbacks: {
                whileScrolling: function() {
                    $(this).trigger('mCSB.scroll');
                }
            }
        },

        events: {
            'click [data-role="sidebar-add-widget"]': 'onClickAddWidget',
            'click [data-role="sidebar-resize"]': 'onClickSidebarToggle',
            'click [data-role="sidebar-toggle"]': 'onClickSidebarToggle'
        },

        listen: {
            'change:state model': 'onSidebarStateChange',
            'change:state collection': 'onWidgetStateChange'
        },

        /**
         * @inheritDoc
         */
        constructor: function SidebarView(options) {
            SidebarView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (this.model.isMaximized()) {
                this.collection.each(function(widget) {
                    if (widget.get('state') === constants.WIDGET_MAXIMIZED_HOVER) {
                        widget.set({state: constants.WIDGET_MAXIMIZED});
                    }
                });
            }
            SidebarView.__super__.initialize.call(this, options);
        },

        getAvailableWidgets: function() {
            var widgetCollection = this.collection;
            return _.map(this.availableWidgets, function(widgetObject, widgetName) {
                return _.defaults({
                    widgetName: widgetName,
                    added: widgetCollection.where({widgetName: widgetName}).length
                }, widgetObject);
            });
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var data = SidebarView.__super__.getTemplateData.call(this);
            _.extend(data, this.model.toJSON(), {
                isMaximized: this.model.isMaximized()
            });
            return data;
        },

        /**
         * @inheritDoc
         */
        render: function() {
            SidebarView.__super__.render.call(this);

            var isMaximized = this.model.isMaximized();

            this.$el.toggleClass('maximized', isMaximized);
            this.$el.toggleClass('minimized', !isMaximized);
            this.$list.sortable({
                axis: 'y',
                containment: 'parent',
                delay: constants.WIDGET_SORT_DELAY,
                revert: true,
                tolerance: 'pointer',
                handle: '[data-role="sidebar-widget-icon"]',
                start: function(event, ui) {
                    var model = this.collection.get(ui.item.data('cid'));
                    if (model) {
                        model.isDragged = true;
                    }
                }.bind(this),
                stop: function(event, ui) {
                    var model = this.collection.get(ui.item.data('cid'));
                    if (model) {
                        model.isDragged = false;
                    }
                    this.reorderWidgets();
                }.bind(this)
            });

            this.$('[data-role="sidebar-scroll-container"]').mCustomScrollbar(this.mcsOptions);

            mediator.trigger('layout:adjustHeight');

            return this;
        },

        /**
         * @inheritDoc
         */
        initItemView: function(model) {
            var subview = SidebarView.__super__.initItemView.call(this, model);
            this.listenTo(subview, {
                removeWidget: this.onRemoveWidget,
                setupWidget: this.onSetupWidget
            });
            return subview;
        },

        /**
         * @inheritDoc
         */
        removeViewForItem: function(item) {
            var subview = this.subview('itemView:' + item.cid);
            if (!subview) {
                this.stopListening(subview);
            }
            SidebarView.__super__.removeViewForItem.call(this, item);
        },

        reorderWidgets: function() {
            var ids = this.$list.sortable('toArray', {attribute: 'data-cid'});
            var widgetOrder = _.object(ids, _.range(ids.length));

            this.collection.each(function(widget) {
                var order = widgetOrder[widget.cid];
                if (widget.get('position') !== order) {
                    widget.set({position: order}, {silent: true});
                    widget.save();
                }
            });

            this.collection.sort();
        },

        onSidebarStateChange: function() {
            this.$el.toggleClass('maximized', this.model.isMaximized());
            this.$el.toggleClass('minimized', !this.model.isMaximized());
            mediator.trigger('layout:adjustHeight');
        },

        onWidgetStateChange: function(widgetModel) {
            if (widgetModel.get('state') === constants.WIDGET_MAXIMIZED_HOVER) {
                // close other hovered widgets
                this.collection.each(function(model) {
                    if (model !== widgetModel) {
                        model.removeHoverState();
                    }
                });
            }
        },

        onClickAddWidget: function(e) {
            e.preventDefault();

            var widgetAddView = new WidgetPickerModal({
                sidebarPosition: this.model.get('position'),
                availableWidgets: this.getAvailableWidgets(),
                widgetCollection: this.collection,
                allowOk: false
            });

            widgetAddView.open();
        },

        onClickSidebarToggle: function(e) {
            e.preventDefault();

            this.model.toggleState();
            this.model.save();
        },

        onRemoveWidget: function(subview) {
            var modal = new DeleteConfirmation({
                content: __('oro.sidebar.widget.remove.confirm.message')
            });

            modal.on('ok', function() {
                subview.model.destroy();
                modal.off();
            });

            modal.on('cancel', function() {
                modal.off();
            });

            modal.open();
        },

        onSetupWidget: function(subview) {
            var widgetModel = subview.model;
            widgetModel.loadModule().then(function(widgetModule) {
                var widgetSetupModal = new WidgetSetupModalView({
                    model: widgetModel,
                    contentView: widgetModule.SetupView,
                    okCloses: false,
                    snapshot: JSON.stringify(widgetModel)
                });

                widgetSetupModal.open();
            });
        }
    });

    return SidebarView;
});
