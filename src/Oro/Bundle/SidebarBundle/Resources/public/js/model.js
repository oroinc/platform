define(function(require) {
    'use strict';

    var SidebarModel;
    var _ = require('underscore');
    var Backbone = require('backbone');
    var constants = require('./constants');

    SidebarModel = Backbone.Model.extend({
        defaults: {
            position: constants.SIDEBAR_LEFT,
            state: constants.SIDEBAR_MINIMIZED
        },

        /**
         * @inheritDoc
         */
        initialize: function(data, options) {
            _.extend(this, _.pick(options, ['urlRoot']));
            SidebarModel.__super__.initialize.apply(this, arguments);
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
        }
    });

    return SidebarModel;
});
