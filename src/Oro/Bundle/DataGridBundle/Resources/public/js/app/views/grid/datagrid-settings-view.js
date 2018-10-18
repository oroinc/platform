define(function(require) {
    'use strict';

    var DatagridSettingsView;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var TabCollectionView = require('oroui/js/app/views/tab-collection-view');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var template = require('tpl!orodatagrid/templates/datagrid/grid-settings.html');
    var mediator = require('oroui/js/mediator');

    /**
     * @class DatagridSettingsView
     * @extends BaseView
     */
    DatagridSettingsView = BaseView.extend({
        /**
         * @inheritDoc
         */
        optionNames: ['viewConstructors', 'title', 'template'],

        /**
         * @inheritDoc
         */
        autoRender: true,

        /**
         * @inheritDoc
         */
        template: template,

        /**
         * @property {String}
         */
        title: _.__('oro.datagrid.settings.title'),

        /**
         * @property {String}
         */
        tabsNav: '[data-tabs-nav]',

        /**
         * @property {String}
         */
        tabsContent: '[data-tabs-content]',

        /**
         * @property {Array}
         */
        views: null,

        /**
         * @property
         */
        uniqueId: null,

        /**
         * @inheritDoc
         */
        constructor: function DatagridSettingsView() {
            DatagridSettingsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         * @param options
         */
        initialize: function(options) {
            this.options = options;
            DatagridSettingsView.__super__.initialize.apply(this, arguments);

            this.uniqueId = _.uniqueId(this.cid);
            this.views = new BaseCollection(
                _.map(this.viewConstructors, function(view, index) {
                    if (index === 0) {
                        view.active = true;
                    }

                    view.uniqueId = _.uniqueId(view.id);
                    view.id = 'datagrid-settings-' + view.id + '-' + this.uniqueId;
                    return view;
                }, this)
            );

            this.setElement(options._sourceElement);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            DatagridSettingsView.__super__.dispose.call(this);
        },

        /**
         * Listen change active tab
         * @param model
         */
        onTabChange: function(model) {
            this.views.each(function(category) {
                this.getElement(category.id).hide();
            }, this);

            if (model.hasChanged('active') && model.get('active') === true) {
                this.getElement(model.id).show();
                this.subview(model.id).updateViews();
                this.getElement(model.get('uniqueId')).tab('show');
            }
        },

        /**
         * Get current element
         * @param elementName
         * @returns {JQuery | jQuery | HTMLElement}
         */
        getElement: function(elementName) {
            return $('#' + elementName);
        },

        /**
         * Render array of views, create tabs view
         * @inheritDoc
         */
        render: function() {
            DatagridSettingsView.__super__.render.call(this);

            this.tabs = new TabCollectionView({
                el: this.$(this.tabsNav),
                animationDuration: 0,
                collection: this.views,
                useDropdown: this.options.useDropdown
            });

            this.views.each(this.renderSubview, this);
            this.listenTo(this.views, 'change', this.onTabChange);
            mediator.execute('hideLoading');
        },

        /**
         * Render subview from params
         * @param view
         */
        renderSubview: function(view) {
            var id = view.get('id');
            var constructor = view.get('view');

            this.subview(id, new constructor(_.extend({
                _sourceElement: this.$('#' + id),
                grid: this.options.grid,
                columns: this.options.columns
            }, view.get('options'))));
        },

        getTemplateData: function() {
            return {
                title: this.title,
                views: this.views.toJSON(),
                viewId: this.uniqueId
            };
        },

        /**
         * Update views element
         */
        updateViews: function() {
            _.invoke(this.subviews, 'updateViews');
        },

        beforeOpen: function(e) {
            _.invoke(this.subviews, 'beforeOpen', e);
        }
    });

    return DatagridSettingsView;
});
