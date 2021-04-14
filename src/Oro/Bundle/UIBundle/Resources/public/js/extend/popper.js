define(function(require) {
    'use strict';

    const Popper = require('popper');
    const _ = require('underscore');

    function getStyleComputedProperty(element, property) {
        if (element.nodeType !== 1) {
            return [];
        }
        // NOTE: 1 DOM access here
        const window = element.ownerDocument.defaultView;
        const css = window.getComputedStyle(element, null);
        return property ? css[property] : css;
    }

    /**
     * Given element offsets, generate an output similar to getBoundingClientRect
     * @method
     * @memberof Popper.Utils
     * @argument {Object} offsets
     * @returns {Object} ClientRect like output
     */
    function getClientRect(offsets) {
        return Object.assign({}, offsets, {
            right: offsets.left + offsets.width,
            bottom: offsets.top + offsets.height
        });
    }

    function getOffsetRectRelativeToArbitraryNode(children, parent, fixedPosition = false) {
        const isHTML = parent.nodeName === 'HTML';
        const childrenRect = children.getBoundingClientRect();
        const parentRect = parent.getBoundingClientRect();

        const styles = getStyleComputedProperty(parent);
        const borderTopWidth = parseFloat(styles.borderTopWidth, 10);
        const borderLeftWidth = parseFloat(styles.borderLeftWidth, 10);

        // In cases where the parent is fixed, we must ignore negative scroll in offset calc
        if (fixedPosition && isHTML) {
            parentRect.top = Math.max(parentRect.top, 0);
            parentRect.left = Math.max(parentRect.left, 0);
        }
        const offsets = getClientRect({
            top: childrenRect.top - parentRect.top - borderTopWidth,
            left: childrenRect.left - parentRect.left - borderLeftWidth,
            width: childrenRect.width,
            height: childrenRect.height
        });
        offsets.marginTop = 0;
        offsets.marginLeft = 0;

        // Subtract margins of documentElement in case it's being used as parent
        // we do this only on HTML because it's the only element that behaves
        // differently when margins are applied to it. The margins are included in
        // the box of the documentElement, in the other cases not.
        if (isHTML) {
            const marginTop = parseFloat(styles.marginTop, 10);
            const marginLeft = parseFloat(styles.marginLeft, 10);

            offsets.top -= borderTopWidth - marginTop;
            offsets.bottom -= borderTopWidth - marginTop;
            offsets.left -= borderLeftWidth - marginLeft;
            offsets.right -= borderLeftWidth - marginLeft;

            // Attach marginTop and marginLeft because in some circumstances we may need them
            offsets.marginTop = marginTop;
            offsets.marginLeft = marginLeft;
        }

        return offsets;
    }

    Popper.Defaults.rtl = _.isRTL();
    Popper.Defaults.modifiers.adjustHeight = {
        order: 550,
        enabled: false,
        fn: function(data, options) {
            const element = data.instance.popper;
            const html = element.ownerDocument.documentElement;
            const relativeOffset = getOffsetRectRelativeToArbitraryNode(element, html);
            const scrollElement = data.instance.state.scrollElement;
            let clientRect;
            let availableHeight;
            if (scrollElement.tagName.toUpperCase() === 'BODY') {
                availableHeight = scrollElement.parentElement.clientHeight - relativeOffset.top;
            } else {
                clientRect = scrollElement.getBoundingClientRect();
                availableHeight = clientRect.top + clientRect.height - relativeOffset.top;
            }

            if (data.popper.height > availableHeight) {
                data.styles.maxHeight = availableHeight + 'px';
                data.attributes['x-adjusted-height'] = '';
            } else {
                data.styles.maxHeight = 'none';
                data.attributes['x-adjusted-height'] = false;
            }
            return data;
        }
    };

    Popper.Defaults.modifiers.flip = {
        behavior: 'flip',
        boundariesElement: 'viewport',
        enabled: true,
        order: 600,
        padding: 5,
        fn: function flip(data, options) {
            if (
                ['left', 'right'].includes(data.placement) &&
                data.instance.options.rtl
            ) {
                const placementRTLMap = {
                    left: 'right',
                    right: 'left'
                };
                data.placement = data.placement.replace(
                    /right|left/g,
                    matched => placementRTLMap[matched]
                );
            }
            return data;
        }
    };

    return Popper;
});
