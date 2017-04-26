define([
    'jquery',
    'oroui/js/error'
], function($, error) {
    'use strict';
    $(document).ajaxError($.proxy(error.handle, error));
});
