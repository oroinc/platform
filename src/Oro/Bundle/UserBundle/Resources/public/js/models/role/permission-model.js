define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');

    const PermissionModel = BaseModel.extend({
        _initialAccessLevel: null,

        /**
         * @inheritdoc
         */
        constructor: function PermissionModel(attrs, options) {
            PermissionModel.__super__.constructor.call(this, attrs, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(attrs, options) {
            this._initialAccessLevel = this.get('access_level');
            PermissionModel.__super__.initialize.call(this, attrs, options);
        },

        isAccessLevelChanged: function() {
            return this._initialAccessLevel !== this.get('access_level');
        }
    });

    return PermissionModel;
});
