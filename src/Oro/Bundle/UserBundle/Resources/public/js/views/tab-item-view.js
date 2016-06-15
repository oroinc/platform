define(function(require) {
    'use strict';

    var TabItemView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orouser/js/views/role-view
     */
    TabItemView = BaseView.extend({
        tagName: 'li',
        className: 'tab',
        template: require('tpl!orouser/templates/tab-item.html'),
        autoRender: true,
        listen: {
            'change:active model': 'updateState'
        },
        events: {
            'click a': 'onSelect'
        },
        updateState: function() {
            this.$el.toggleClass('active', this.model.get('active'));
        },
        onSelect: function() {
            this.model.set('active', true);
        }
    });

    return TabItemView;
});
