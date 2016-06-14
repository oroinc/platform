define(function(require) {
    'use strict';

    var PermissionModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    PermissionModel = BaseModel.extend({
        _originalValue: null,

        initialize: function() {
            this._originalValue = this.get('value');
            PermissionModel.__super__.initialize.apply(this, arguments);
        },

        isValueChanged: function() {
            return this._originalValue !== this.get('value');
        }
    });

    return PermissionModel;
});
