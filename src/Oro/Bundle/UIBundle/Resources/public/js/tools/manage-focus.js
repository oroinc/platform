import $ from 'jquery';

const TAB_KEY_CODE = 9;

export default {
    /**
     * Set focus to the first match
     * @param {jQuery.Element} $container
     * @param {jQuery.Element} [$el=null]
     */
    focusTabbable: function($container, $el = null) {
        let $focusableEl = $el;

        // 1. An element that was passed
        // 2. First element inside the container matching [autofocus]
        // 3. Tabbable element inside the container

        if (!$focusableEl) {
            $focusableEl = $container.find('[autofocus]');
        }

        if (!$focusableEl.length) {
            $focusableEl = $container.find(':tabbable');
        }

        $focusableEl.eq(0).trigger('focus');
    },

    /**
     * Prevent tabbing out of container
     * @param {object} event
     * @param {DOM.Element|jQuery.Element} container
     */
    preventTabOutOfContainer(event, container) {
        const $container = container instanceof $ ? container : $(container);

        if (event.keyCode !== TAB_KEY_CODE || event.isDefaultPrevented()) {
            return;
        }

        const $tabbableElements = $container.find(':tabbable');
        const $firstTabbable = $tabbableElements.first();
        const $lastTabbable = $tabbableElements.last();

        if (
            (event.target === $lastTabbable[0] || event.target === $container[0]) &&
            !event.shiftKey
        ) {
            $firstTabbable.trigger('focus');
            event.preventDefault();
        } else if (
            (event.target === $firstTabbable[0] || event.target === $container[0]) &&
            event.shiftKey
        ) {
            $lastTabbable.trigger('focus');
            event.preventDefault();
        }
    }
};
