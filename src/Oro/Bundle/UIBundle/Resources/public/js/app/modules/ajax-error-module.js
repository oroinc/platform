define([
    'jquery',
    'oroui/js/error'
], function($, error) {
    'use strict';
    $(document).ajaxError(error.handle.bind(error));
});
