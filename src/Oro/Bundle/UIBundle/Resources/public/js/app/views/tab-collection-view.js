import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import BaseCollectionView from 'oroui/js/app/views/base/collection-view';
import moduleConfig from 'module-config';
import TabItemView from './tab-item-view';
import template from 'tpl-loader!oroui/templates/tab-collection-container.html';

const config = {
    templateClassName: 'nav nav-tabs responsive-tabs',
    ...moduleConfig(module.id)
};

const TabCollectionView = BaseCollectionView.extend({
    listSelector: '[data-name="tabs-list"]',
    className: 'tab-collection oro-tabs clearfix',
    itemView: TabItemView,
    useDropdown: false,
    listen: {
        'change collection': 'onChange'
    },

    template,

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
        this.initLayout().then(this.handleLayoutInit.bind(this));
    },

    handleLayoutInit: function() {
        mediator.trigger('widget:doRefresh');
    }
});

export default TabCollectionView;
