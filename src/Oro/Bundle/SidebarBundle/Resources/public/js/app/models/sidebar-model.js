define(function(require) {
    'use strict';

    var SidebarModel;
    var _ = require('underscore');
    var BaseModel = require('oroui/js/app/models/base/model');
    var constants = require('orosidebar/js/sidebar-constants');

    SidebarModel = BaseModel.extend({
        defaults: {
            position: constants.SIDEBAR_LEFT,
            state: constants.SIDEBAR_MINIMIZED
        },

        /**
         * @inheritDoc
         */
        constructor: function SidebarModel(data, options) {
            SidebarModel.__super__.constructor.call(this, data, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(data, options) {
            _.extend(this, _.pick(options, ['urlRoot']));
            SidebarModel.__super__.initialize.call(this, data, options);
        },

        /**
         * Toggles state of sidebar between minimized and maximized
         */
        toggleState: function() {
            switch (this.get('state')) {
                case constants.SIDEBAR_MINIMIZED:
                    this.set('state', constants.SIDEBAR_MAXIMIZED);
                    break;

                case constants.SIDEBAR_MAXIMIZED:
                    this.set('state', constants.SIDEBAR_MINIMIZED);
                    break;
            }
        },

        /**
         * Check if current state in model is SIDEBAR_MAXIMIZED
         *
         * @return {boolean}
         */
        isMaximized: function() {
            return this.get('state') === constants.SIDEBAR_MAXIMIZED;
        },

        /**
         * Check if current state in model is SIDEBAR_MINIMIZED
         *
         * @return {boolean}
         */
        isMinimized: function() {
            return this.get('state') === constants.SIDEBAR_MINIMIZED;
        }
    });

    return SidebarModel;
});
