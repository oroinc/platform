define([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    BaseController.loadBeforeAction([
        'jquery',
        'orosidebar/js/app/components/sidebar-component'
    ], function($, SidebarComponent) {
        $('.sidebar[data-page-component-options]').each(function(i, elem) {
            BaseController.addToReuse('emailNotification' + i, {
                compose: function() {
                    var $sourceElement = $(elem);
                    var options = $sourceElement.data('pageComponentOptions');
                    $sourceElement.removeAttrs('data-page-component-options');
                    options._sourceElement = $sourceElement;
                    this.component = new SidebarComponent(options);
                }
            });
        });
    });
});
