define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseModel = require('oroui/js/app/models/base/model');
    const constants = require('orosidebar/js/sidebar-constants');

    const SidebarModel = BaseModel.extend({
        defaults: {
            position: constants.SIDEBAR_LEFT,
            state: constants.SIDEBAR_MINIMIZED
        },

        /**
         * @inheritdoc
         */
        constructor: function SidebarModel(data, options) {
            SidebarModel.__super__.constructor.call(this, data, options);
        },

        /**
         * @inheritdoc
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
