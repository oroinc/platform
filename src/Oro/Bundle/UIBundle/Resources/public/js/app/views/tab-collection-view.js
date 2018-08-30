define(function(require) {
    'use strict';

    var TabCollectionView;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var module = require('module');
    var config = module.config();
    var TabItemView = require('./tab-item-view');

    config = _.extend({
        templateClassName: 'nav nav-tabs'
    }, config);

    TabCollectionView = BaseCollectionView.extend({
        listSelector: '[data-name="tabs-list"]',
        className: 'tab-collection oro-tabs clearfix',
        itemView: TabItemView,
        useDropdown: false,
        itemsWidth: 0,
        itemsMaxWidth: 0,
        dropdownTemplate: require('tpl!oroui/templates/dropdown-control.html'),
        dropdown: '[data-dropdown]',
        dropdownWrapper: '[data-dropdown-wrapper]',
        dropdownToggle: '[data-toggle="dropdown"]',
        events: {
            'click a': function(e) {
                e.preventDefault();
            }
        },
        listen: {
            'change collection': 'onChange'
        },

        template: function() {
            return '<ul class="' + config.templateClassName + '" role="tabpanel" data-name="tabs-list"></ul>';
        },

        /**
         * @inheritDoc
         */
        constructor: function TabCollectionView() {
            TabCollectionView.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            _.extend(this, _.defaults(_.pick(options, ['useDropdown']), this));

            TabCollectionView.__super__.initialize.apply(this, arguments);
        },

        onChange: function(changedModel) {
            if (changedModel.get('active')) {
                this.collection.each(function(model) {
                    if (model !== changedModel) {
                        model.set('active', false);
                    }
                });

                if (this.useDropdown) {
                    this.dropdownUpdate();
                }
            }
        },

        _ensureElement: function() {
            TabCollectionView.__super__._ensureElement.call(this);
            this.$el.addClass(_.result(this, 'className'));
        },

        dropdownInit: function() {
            this.itemsWidth = this.calcItems();

            this.$el.find(this.listSelector).append(this.dropdownTemplate());

            this.dropdownUpdateState();

            mediator.on('layout:reposition', _.debounce(this.dropdownUpdate, 100), this);
        },

        dropdownUpdateState: function(model) {
            var $dropdownToggle = this.$el.find(this.dropdownToggle);
            this.$el.find(this.dropdown).removeClass('active');

            if (model && this.getItemView(model).$el.closest(this.dropdownWrapper).length) {
                $dropdownToggle.html(model.get('label'));
                this.$el.find(this.dropdown).addClass('active');
            } else {
                $dropdownToggle.html($dropdownToggle.data('dropdown-placeholder'));
            }
        },

        dropdownUpdate: function() {
            var $tabsContainer = this.$el.find(this.listSelector);
            var dropdownContainerWidth = $tabsContainer.width();

            this.dropdownUpdateState();

            if (!$tabsContainer.is(':visible')) {
                return;
            }

            this.dropdownContainerWidth = dropdownContainerWidth;

            if (this.dropdownContainerWidth > this.itemsWidth) {
                this.$el.find(this.dropdown).hide();

                this.$el.find(this.listSelector).prepend(_.map(this.getItemViews(), function(view) {
                    return view.el;
                }));
            } else {
                this.$el.find(this.dropdown).show();

                var visibleWidth = this.itemsWidth;

                for (var i = this.collection.models.length - 1; i >= 0; i--) {
                    var $currentView = this.getItemView(this.collection.models[i]).$el;

                    if ((visibleWidth + this.getItemsMaxWidth()) < this.dropdownContainerWidth) {
                        this.$el.find(this.listSelector).prepend($currentView);
                    } else {
                        this.$el.find(this.dropdownWrapper).prepend($currentView);
                    }

                    if (this.collection.models[i].get('active')) {
                        this.dropdownUpdateState(this.collection.models[i]);
                    }

                    visibleWidth -= $currentView.data('dropdownOuterWidth');
                }
            }
        },

        dropdownWidth: function() {
            return this.$el.find(this.dropdown).outerWidth(true);
        },

        calcItems: function() {
            var self = this;
            var itemsWidth = 0;

            _.each(this.getItemViews(), function(view) {
                var itemWidth = view.$el.outerWidth(true);
                itemsWidth += itemWidth;
                view.$el.data('dropdownOuterWidth', itemWidth);
                self.itemsMaxWidth = Math.max(self.itemsMaxWidth, itemWidth);
            });

            return itemsWidth;
        },

        getItemsMaxWidth: function() {
            return this.itemsMaxWidth;
        },

        render: function() {
            TabCollectionView.__super__.render.apply(this, arguments);

            if (this.useDropdown) {
                this.dropdownInit();
            }

            mediator.trigger('widget:doRefresh');
        }
    });

    return TabCollectionView;
});
