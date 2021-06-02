import $ from 'jquery';
import manageFocus from 'oroui/js/tools/manage-focus';

/**
 * Perverts focus from getting into container with `[data-ignore-tabbable]` attribute
 */
const onFocusin = event => {
    const $receivingFocus = $(event.target);
    if (!$receivingFocus.is('[data-ignore-tabbable] *')) {
        // nothing to do
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    const allTabbable = $('body').find(':tabbable');
    const index = allTabbable.toArray().indexOf(event.target);
    const reverse = Boolean(event.relatedTarget) &&
        index < allTabbable.toArray().indexOf(event.relatedTarget);
    if (index !== -1) {
        const tabbables = (reverse ? allTabbable.slice(0, index) : allTabbable.slice(index + 1))
            .not('[data-ignore-tabbable] *').toArray();
        const nextTabbable = reverse
            ? manageFocus.getLastTabbable(tabbables)
            : manageFocus.getFirstTabbable(tabbables);

        if (nextTabbable) {
            $(nextTabbable).focus();
            if (nextTabbable.nodeName.toLowerCase() === 'input' && typeof nextTabbable.select !== 'undefined') {
                nextTabbable.select();
            }

            // natural blur action for current receivingFocus element
            return;
        }
    }

    $receivingFocus.blur();
};

document.addEventListener('focusin', onFocusin, true);
