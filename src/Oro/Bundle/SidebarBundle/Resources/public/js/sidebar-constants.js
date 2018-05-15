define(function() {
    'use strict';

    var constants = {};

    /**
     * Left sidebar
     */
    constants.SIDEBAR_LEFT = 'SIDEBAR_LEFT';

    /**
     * Right sidebar
     */
    constants.SIDEBAR_RIGHT = 'SIDEBAR_RIGHT';

    /**
     * Minimized sidebar
     */
    constants.SIDEBAR_MINIMIZED = 'SIDEBAR_MINIMIZED';

    /**
     * Maximized sidebar
     */
    constants.SIDEBAR_MAXIMIZED = 'SIDEBAR_MAXIMIZED';

    /**
     * Minimized widget
     */
    constants.WIDGET_MINIMIZED = 'WIDGET_MINIMIZED';

    /**
     * Maximized widget
     */
    constants.WIDGET_MAXIMIZED = 'WIDGET_MAXIMIZED';

    /**
     * Maximized on hover widget
     */
    constants.WIDGET_MAXIMIZED_HOVER = 'WIDGET_MAXIMIZED_HOVER';

    /**
     * Delay value for options of `jquery.sortable` plugin applied to sidebar widgets
     */
    constants.WIDGET_SORT_DELAY = 100;

    return constants;
});
