import $ from 'jquery';
import manageFocus from 'oroui/js/tools/manage-focus';

/**
 * Perverts focus from getting into container with `[data-ignore-tabbable]` attribute
 */
$(document).on('keydown', function(event) {
    const TAB_KEY_CODE = 9;
    if (event.keyCode !== TAB_KEY_CODE || event.isDefaultPrevented()) {
        return;
    }

    const allTabbable = $('body').find(':tabbable');
    const index = allTabbable.toArray().indexOf(event.target);
    const reverse = event.shiftKey || event.altKey;
    let nextTabbable;
    if (index !== -1) {
        nextTabbable = reverse ? allTabbable.slice(0, index) : allTabbable.slice(index + 1);
        nextTabbable = nextTabbable.not('[data-ignore-tabbable] *').toArray();
        if (reverse) {
            nextTabbable = manageFocus.getLastTabbable(nextTabbable);
        } else {
            nextTabbable = manageFocus.getFirstTabbable(nextTabbable);
        }
    }

    if (nextTabbable) {
        $(nextTabbable).focus();
        event.preventDefault();
    }
});
