import $ from 'jquery';
import ResizableArea from 'oroui/js/app/plugins/plugin-resizable-area';

$(document).on('initLayout', function(e) {
    ResizableArea.setPreviousState($(e.target));
});
