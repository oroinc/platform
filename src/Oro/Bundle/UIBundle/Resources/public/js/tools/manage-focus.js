import $ from 'jquery';

const TAB_KEY_CODE = 9;

export default {
    /**
     * Set focus to the first match
     * @param {jQuery.Element} $container
     * @param {jQuery.Element} [$el=null]
     */
    focusTabbable($container, $el = null) {
        // 1. An element that was passed
        // 2. First element inside the container matching [autofocus]
        // 3. Tabbable element inside the container

        let $elToFocus = $el !== null ? $el : $container.find('[autofocus]:first');

        if (!$elToFocus.length) {
            $elToFocus = $(this.getFirstTabbable($container.find(':tabbable').toArray()));
        }

        $elToFocus.focus();
    },

    /**
     * Find first tabbable element from array taking in account that in array can be present focusable group of elements
     * (e.g. radio buttons) state and order of which can impact focusability each others. Param `preferFirstOfGroup`
     * allows to manage if first or last element of focusable group will be returned.
     *
     * @param {Array.<HTMLElement>} elements
     * @param {boolean} preferFirstOfGroup
     * @return {HTMLElement|undefined}
     */
    getFirstTabbable(elements, preferFirstOfGroup = true) {
        return elements.find((element, i) => {
            if ($('input[type=radio]').is(element) && !element.checked) {
                const name = element.getAttribute('name');

                if ($(`input[type=radio][name='${name}']:checked`).length > 0) {
                    return false;
                }

                if (!preferFirstOfGroup) {
                    if (i < elements.length - 1 && $(`input[type=radio][name='${name}']`).is(elements[i + 1])) {
                        return false;
                    }
                } else if (i > 0 && $(`input[type=radio][name='${name}']`).is(elements[i - 1])) {
                    return false;
                }
            }

            return true;
        });
    },

    getLastTabbable(elements, preferFirstOfGroup = true) {
        return this.getFirstTabbable(Array.from(elements).reverse(), !preferFirstOfGroup);
    },

    /**
     * Prevent tabbing out of container
     * @param {object} e
     * @param {DOM.Element|jQuery.Element} container
     */
    preventTabOutOfContainer(e, container) {
        if (e.keyCode !== TAB_KEY_CODE || e.isDefaultPrevented()) {
            return;
        }

        const $container = container instanceof $ ? container : $(container);
        const tabbableElements = $container.find(':tabbable').toArray();
        let substitutionElement;

        if (e.shiftKey) {
            if ($container.is(e.target) || this.getFirstTabbable(tabbableElements, false) === e.target) {
                substitutionElement = this.getLastTabbable(tabbableElements, false);
            }
        } else if ($container.is(e.target) || this.getLastTabbable(tabbableElements) === e.target) {
            substitutionElement = this.getFirstTabbable(tabbableElements);
        }

        if (substitutionElement) {
            $(substitutionElement).focus();
            e.preventDefault();
        }
    }
};
