define(function(require) {
    'use strict';

    var PermissionCollectionView;
    var mediator = require('oroui/js/mediator');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var PermissionView = require('./permission-view');

    PermissionCollectionView = BaseCollectionView.extend({
        tagName: 'ul',
        className: 'action-permissions',
        animationDuration: 0,
        itemView: PermissionView,
        listen: {
            'change collection': 'onPermissionChange'
        },

        onPermissionChange: function(model) {
            mediator.trigger('securityAccessLevelsComponent:link:click', {
                accessLevel: model.get('value'),
                identityId: model.get('identity'),
                permissionName: model.get('name')
            });
        },

        initItemView: function(model) {
            return new this.itemView({
                autoRender: false,
                model: model,
                accessLevels: this.collection.accessLevels
            });
        }
    });

    return PermissionCollectionView;
});
