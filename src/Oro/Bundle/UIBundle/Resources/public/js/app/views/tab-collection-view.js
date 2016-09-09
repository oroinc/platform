define(function(require) {
    'use strict';

    var TabCollectionView;
    var _ = require('underscore');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var TabItemView = require('./tab-item-view');

    TabCollectionView = BaseCollectionView.extend({
        listSelector: '[data-name="tabs-list"]',
        className: 'tab-collection oro-tabs clearfix',
        itemView: TabItemView,
        events: {
            'click a': function(e) {
                e.preventDefault();
            }
        },
        listen: {
            'change collection': 'onChange'
        },

        template: function() {
            return '<ul class="nav nav-tabs" data-name="tabs-list"></ul>';
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
        }
    });

    return TabCollectionView;
});
