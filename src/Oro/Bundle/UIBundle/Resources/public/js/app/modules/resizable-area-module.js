define(function(require) {
    'use strict';

    var $ = require('jquery');
    var ResizableArea = require('oroui/js/app/plugins/plugin-resizable-area');

    $(document).on('initLayout', function(e) {
        ResizableArea.setPreviousState($(e.target));
    });
});
