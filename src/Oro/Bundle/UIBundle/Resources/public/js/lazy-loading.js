/* jshint browser:true */
/* global require */
require(['jquery', 'oro/widget-manager'], function($, widgetManager) {
    'use strict';

    $(function () {
        $('a[data-lazy-loading]').on('click', function(e) {
            var alias = $(e.currentTarget).data('alias');

            widgetManager.getWidgetInstanceByAlias(alias, function (widget) {
                if (widget.firstRun) {
                    widget.render();
                }
            });
        });
    });
});
