define(function(require) {
    'use strict';

    var SidebarRecentEmailsComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');

    SidebarRecentEmailsComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.model = options.model;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            SidebarRecentEmailsComponent.__super__.dispose.call(this);
        }
    });

    return SidebarRecentEmailsComponent;
});
