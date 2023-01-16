import $ from 'jquery';
import 'jquery-ui/tabbable';

const TAB_KEY_CODE = 9;

export default {
    /**
     * Set focus to the first match
     * @param {jQuery.Element} $container
     * @param {jQuery.Element} [$el=null]
     */
    focusTabbable($container, $el = null) {
        // 1. An element that was passed
        // 2. First element inside the container matching `.active:tabbable` or `[data-autofocus]:tabbable` or `[autofocus]`
        // 3. Tabbable element inside the container

        let $elToFocus = $el !== null
            ? $el
            : $container.find([
                '.active:tabbable',
                '[data-autofocus]:not([data-autofocus="false"]):tabbable',
                '[autofocus]'
            ].join(',')).first();

        if (!$elToFocus.length) {
            $elToFocus = $(this.getFirstTabbable($container.find(':not([data-autofocus="false"]):tabbable').toArray()));
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

                if ($(`input[type=radio][name='${name}']:visible:checked`).length > 0) {
                    return false;
                }

                if (!preferFirstOfGroup) {
                    if (i < elements.length - 1 && $(`input[type=radio][name='${name}']:visible`).is(elements[i + 1])) {
                        return false;
                    }
                } else if (i > 0 && $(`input[type=radio][name='${name}']:visible`).is(elements[i - 1])) {
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
     * Get next tabbable element
     * @param {HTMLElement[]} elements
     * @param {HTMLElement} target
     * @returns {HTMLElement}
     */
    getNextTabbable(elements, target) {
        let findCurrent = false;
        return elements.find(element => {
            if (element.isSameNode(target)) {
                findCurrent = true;
                return false;
            }

            return findCurrent;
        });
    },

    /**
     * Get previous tabbable element
     * @param {HTMLElement[]} elements
     * @param {HTMLElement} target
     * @returns {HTMLElement}
     */
    getPrevTabbable(elements, target) {
        return this.getNextTabbable(Array.from(elements).reverse(), target);
    },

    /**
     * Get iterated unchecked radio button
     * @param {HTMLElement[]} elements
     * @param {HTMLElement} target
     * @param {boolean} shiftKey
     * @param {boolean} altKey
     * @returns {boolean|HTMLElement}
     */
    isIterateTabbableUncheckedRadio(elements, {target, shiftKey, altKey}) {
        const element = (shiftKey || altKey)
            ? this.getPrevTabbable(elements, target)
            : this.getNextTabbable(elements, target);

        if (!element) {
            return false;
        }

        if ($('input[type=radio]').is(element) && !element.checked) {
            return element;
        }

        return false;
    },

    /**
     * Filter active tabbable radio button from radio group
     *
     * @param {HTMLElement[]} elements
     * @returns {(HTMLElement|Array)}
     */
    omitNotActiveRadioElements(elements) {
        return elements.reduce((collection, element, index) => {
            if (element.type === 'radio') {
                const foundItemIndex = collection.findIndex(item => item.name === element.name);
                if (element.checked) {
                    if (foundItemIndex !== -1) {
                        collection.splice(foundItemIndex, 1, element);
                    } else {
                        collection.push(element);
                    }
                } else if (foundItemIndex === -1) {
                    collection.push(element);
                }
            } else {
                collection.push(element);
            }

            return collection;
        }, []);
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
        const tabbableElements = this.omitNotActiveRadioElements($container.find(':visible:tabbable').toArray());
        let substitutionElement;

        const uncheckedRadio = this.isIterateTabbableUncheckedRadio(tabbableElements, e);
        if (uncheckedRadio) {
            substitutionElement = uncheckedRadio;
        }

        if (e.shiftKey || e.altKey) {
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
