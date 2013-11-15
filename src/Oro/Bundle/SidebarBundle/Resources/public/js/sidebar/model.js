define(['backbone', 'oro/sidebar/constants', 'oro/sidebar/widget-container/collection'],
function (Backbone, constants, WidgetContainerCollection) {
    'use strict';

    /**
     * @export  oro/sidebar/sidebar/model
     * @class oro.sidebar.sidebar.Model
     * @extends Backbone.Model
     */
    var SidebarModel = Backbone.Model.extend({
        urlRoot: 'bundles/orosidebar/api/sidebar',

        initialize: function () {
            this.widgets = new WidgetContainerCollection();

            this.position = constants.SIDEBAR_LEFT;
            this.state = constants.SIDEBAR_MINIMIZED;
        },

        /**
         * Toggles state of sidebar between minimized and maximized
         */
        toggleState: function () {
            switch (this.state) {
                case constants.SIDEBAR_MINIMIZED:
                    this.state = constants.SIDEBAR_MAXIMIZED;
                    break;

                case constants.SIDEBAR_MAXIMIZED:
                    this.state = constants.SIDEBAR_MINIMIZED;
                    break;
            }

            this.trigger('change');
        }
    });

    return SidebarModel;
});
