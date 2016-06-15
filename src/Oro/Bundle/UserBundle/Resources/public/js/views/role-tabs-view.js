define(function(require) {
    'use strict';

    var RoleTabsView;
    var _ = require('underscore');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var TabItemView = require('orouser/js/views/tab-item-view');

    /**
     * @export orouser/js/views/role-view
     */
    RoleTabsView = BaseCollectionView.extend({
        template: require('tpl!orouser/templates/role-tabs.html'),
        listSelector: '.oro-tabs .nav-tabs',
        itemView: TabItemView,
        listen: {
            'change collection': 'onChange'
        },
        onChange: function(model) {
            if (model.get('active')) {
                this.collection.each(function(item) {
                    if (item !== model) {
                        item.set('active', false);
                    }
                });
                this.$(' > :first').toggleClass('role-tabs__multi-group', Boolean(model.get('multi')));
            }
        }
    });

    return RoleTabsView;
});
