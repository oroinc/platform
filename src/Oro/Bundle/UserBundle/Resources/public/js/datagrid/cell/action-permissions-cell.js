define(function(require) {
    "use strict";

    var ActionPermissionsCell;
    var BaseView = require('oroui/js/app/views/base/collection-view');

    ActionPermissionsCell = BaseView.extend({
        tagName: "td",

        initialize: function (options) {
            //
        },

        render: function() {
            var permissions = this.model.get('permissions').map(function(model) {
                return '<span>' + model.get('label') + ':' + model.get('value_text') + '</span>';
            });

            this.$el.html(permissions.join(' / '));
        }
    });

    return ActionPermissionsCell;
    
});
