require([
    'jquery', 'oroui/js/mediator'
], function($, mediator) {
    'use strict';

    $(function() {
        $(document).on('click', '.organization-switcher', function() {
            mediator.execute('showLoading');
            return true;
        });
    });
});
