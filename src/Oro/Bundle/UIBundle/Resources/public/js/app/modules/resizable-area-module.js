define(function(require) {
    'use strict';

    const $ = require('jquery');
    const ResizableArea = require('oroui/js/app/plugins/plugin-resizable-area');

    $(document).on('initLayout', function(e) {
        ResizableArea.setPreviousState($(e.target));
    });
});
