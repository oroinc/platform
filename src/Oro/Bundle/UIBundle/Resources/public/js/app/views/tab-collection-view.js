define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    let config = require('module-config').default(module.id);
    const TabItemView = require('./tab-item-view');

    config = _.extend({
        templateClassName: 'nav nav-tabs responsive-tabs'
    }, config);

    const TabCollectionView = BaseCollectionView.extend({
        listSelector: '[data-name="tabs-list"]',
        className: 'tab-collection oro-tabs clearfix',
        itemView: TabItemView,
        useDropdown: false,
        listen: {
            'change collection': 'onChange'
        },

        template: require('tpl-loader!oroui/templates/tab-collection-container.html'),

        /**
         * @inheritdoc
         */
        constructor: function TabCollectionView(options) {
            TabCollectionView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            _.extend(this, _.defaults(_.pick(options, ['useDropdown']), this));

            TabCollectionView.__super__.initialize.call(this, options);
        },

        onChange: function(changedModel) {
            if (changedModel.get('active')) {
                this.collection.each(function(model) {
                    if (model !== changedModel) {
                        model.set('active', false);
                    }
                });
            }
        },

        _ensureElement: function() {
            TabCollectionView.__super__._ensureElement.call(this);
            this.$el.addClass(_.result(this, 'className'));
        },

        getTemplateData: function() {
            const data = TabCollectionView.__super__.getTemplateData.call(this);

            data.templateClassName = config.templateClassName;
            data.tabOptions = {
                useDropdown: this.useDropdown
            };

            return data;
        },

        render: function() {
            TabCollectionView.__super__.render.call(this);

            this.$el.attr('data-layout', 'separate');
            this.initLayout().done(this.handleLayoutInit.bind(this));
        },

        handleLayoutInit: function() {
            mediator.trigger('widget:doRefresh');
        }
    });

    return TabCollectionView;
});
