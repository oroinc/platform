define(['backbone', 'oro/constants', 'oro/collection/widget'],
    function (Backbone, Constants, WidgetCollection) {
    'use strict';

    var SidebarModel = Backbone.Model.extend({
        initialize: function () {
            this.widgets = new WidgetCollection();

            this.position = Constants.SIDEBAR_LEFT;
            this.state = Constants.SIDEBAR_MINIMIZED;
        },

        toggleState: function () {
            switch (this.state) {
                case Constants.SIDEBAR_MINIMIZED:
                    this.state = Constants.SIDEBAR_MAXIMIZED;
                    break;

                case Constants.SIDEBAR_MAXIMIZED:
                    this.state = Constants.SIDEBAR_MINIMIZED;
                    break;
            }

            this.trigger('change');
        }
    });

    return SidebarModel;
});