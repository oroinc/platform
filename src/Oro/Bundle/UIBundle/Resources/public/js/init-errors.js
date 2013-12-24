/* jshint browser:true */
/* global require */
require(['jquery', 'oro/mediator'],
function($, mediator) {
    'use strict';

    $(function() {
        setTimeout(function() {
            mediator.trigger('layout:adjustHeight');
            // emulates 'document ready state' for selenium tests
            document['page-rendered'] = true;
        }, 100);
    });
});
