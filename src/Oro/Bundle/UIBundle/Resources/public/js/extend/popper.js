import {isRTL} from 'underscore';
import Popper from 'popper';
import {getBoundaries, getOffsetRectRelativeToArbitraryNode} from 'popper-utils';

Object.assign(Popper.Defaults, {
    onDestroy: () => {}
});
Object.assign(Popper.Defaults.modifiers, {
    adjustHeight: {
        order: 550,
        enabled: false,
        fn(data) {
            const element = data.instance.popper;
            const html = element.ownerDocument.documentElement;
            const relativeOffset = getOffsetRectRelativeToArbitraryNode(element, html);
            const boundaries = _getBoundaries(data);
            const availableHeight = Math.floor(boundaries.bottom + boundaries.top - relativeOffset.top);

            if (data.popper.height > availableHeight) {
                data.styles.maxHeight = availableHeight + 'px';
                data.attributes['x-adjusted-height'] = '';
            } else {
                data.styles.maxHeight = 'none';
                data.attributes['x-adjusted-height'] = false;
            }
            return data;
        }
    },
    fullscreenable: {
        order: 560,
        enabled: false,
        fn(data) {
            const {instance} = data;
            const boundaries = _getBoundaries(data);
            const availableHeight = Math.floor(boundaries.bottom - data.offsets.reference.bottom);
            const popperHeight = Math.ceil(instance.popper.getBoundingClientRect().height);
            if (popperHeight && !instance.state.hasOwnProperty('isFullscreen')) {
                // popper element has shown (has rect dimensions) and isFullscreen isn't defined yet
                instance.state.isFullscreen = popperHeight > availableHeight;
            }

            if (instance.state.isFullscreen) {
                data.styles.maxHeight = `${boundaries.height}px`;
                data.styles.top = '0';
                data.styles.transform = 'none';
                data.attributes['x-fullscreen'] = '';
                data.attributes['x-placement'] = false;
            } else {
                data.styles.maxHeight = 'none';
                data.attributes['x-fullscreen'] = false;
                if (data.instance.popper.getAttribute('x-fullscreen') !== null) {
                    // schedule one more update cycle after fullscreen is turned off
                    data.instance.scheduleUpdate();
                }
            }

            return data;
        }
    },
    positionFixedBoundary: {
        order: 210,
        enabled: false,
        offset: 8,
        fn(data, options) {
            const {offset} = options;
            const windowWidth = document.documentElement.clientWidth;

            if (data.popper.width > windowWidth) {
                return data;
            }

            const newOffset = data.popper.width + offset > windowWidth ? 0 : offset;

            // Popover is out of the screen on the left side
            if (data.popper.left < 0) {
                data.popper.left = newOffset;
                data.offsets.right = data.offsets.right - newOffset;
                data.offsets.popper.left = newOffset;
                data.offsets.popper.right = data.offsets.popper.right - newOffset;
            // Popover is out of the screen on the right side
            } else if (data.popper.left + data.popper.width > windowWidth) {
                data.popper.left = windowWidth - newOffset - data.popper.width;
                data.popper.right = windowWidth - newOffset;
                data.offsets.popper.left = windowWidth - newOffset - data.popper.width;
                data.offsets.popper.right = windowWidth - newOffset;
            }

            return data;
        }
    },
    rtl: {
        order: 650,
        enabled: isRTL(),
        fn(data, options) {
            if (data.originalPlacement) {
                data.originalPlacement = swapPlacement(data.originalPlacement);
            }

            if (data.placement) {
                data.placement = swapPlacement(data.placement);
                data.instance.options.placement = swapPlacement(data.instance.options.placement);
            }

            if (data.attributes['x-placement']) {
                data.attributes['x-placement'] = swapPlacement(data.attributes['x-placement']);
            }

            data.instance.scheduleUpdate();

            options.enabled = false;
            return data;
        }
    }
});

class OroPopper extends Popper {
    constructor(reference, popper, options = {}) {
        super(reference, popper, options);
    }

    destroy() {
        // Popper element is already removed from the DOM
        // See: https://github.com/floating-ui/floating-ui/blob/v1.16.1/dist/popper.js#L931-L933
        if (this.options.removeOnDestroy && !document.contains(this.popper)) {
            this.options.removeOnDestroy = false;
        }
        this.options.onDestroy(this);
        this.popper.removeAttribute('x-fullscreen');
        this.popper.removeAttribute('x-adjusted-height');
        this.popper.style.maxHeight = '';
        return super.destroy();
    }
}

function swapPlacement(placement) {
    const placementRTLMap = {
        left: 'right',
        right: 'left',
        start: 'end',
        end: 'start'
    };

    if (placement.search(/right|left/g) !== -1) {
        placement = placement.replace(
            /right|left/g,
            matched => placementRTLMap[matched]
        );
    }

    if (placement.search(/top|bottom/g) !== -1) {
        placement = placement.replace(
            /start|end/g,
            matched => placementRTLMap[matched]
        );
    }

    return placement;
}

function _getBoundaries(data) {
    const {instance, positionFixed} = data;
    const {padding, boundariesElement} = instance.modifiers.find(({name}) => name === 'flip');
    return getBoundaries(instance.popper, instance.reference, padding, boundariesElement, positionFixed);
}

export default OroPopper;
