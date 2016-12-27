define([
    'jquery',
    'oroui/js/error'
], function($, Error) {
    'use strict';
    $(document).ajaxError($.proxy(Error.handle, Error));
});
