define(function(require, exports, module) {
    'use strict';

    var TabCollectionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var config = require('module-config').default(module.id);
    var TabItemView = require('./tab-item-view');

    config = _.extend({
        templateClassName: 'nav nav-tabs responsive-tabs'
    }, config);

    TabCollectionView = BaseCollectionView.extend({
        listSelector: '[data-name="tabs-list"]',
        className: 'tab-collection oro-tabs clearfix',
        itemView: TabItemView,
        useDropdown: false,
        events: {
            'click a': 'onTabClick'
        },
        listen: {
            'change collection': 'onChange'
        },

        template: require('tpl-loader!oroui/templates/tab-collection-container.html'),

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

        onTabClick: function(e) {
            var $el = $(e.target);

            e.preventDefault();

            if ($el.closest('.dropdown').find('[data-dropdown-label]').html() !== $el.html()) {
                $el.trigger('shown.bs.tab');
            }
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
            var data = TabCollectionView.__super__.getTemplateData.call(this);

            data.templateClassName = config.templateClassName;
            data.tabOptions = {
                useDropdown: this.useDropdown
            };

            return data;
        },

        render: function() {
            TabCollectionView.__super__.render.apply(this, arguments);

            this.$el.attr('data-layout', 'separate');
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        handleLayoutInit: function() {
            mediator.trigger('widget:doRefresh');
        }
    });

    return TabCollectionView;
});
