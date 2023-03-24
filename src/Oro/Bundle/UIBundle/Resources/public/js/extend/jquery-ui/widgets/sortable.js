import $ from 'jquery';
import 'jquery-ui/widgets/sortable';

let touchHandled;

/**
 * Simulate a mouse event based on a corresponding touch event
 * @param {Object} event A touch event
 * @param {String} simulatedType The corresponding mouse event
 */
function simulateMouseEvent(event, simulatedType) {
    // Ignore multi-touch events
    if (event.originalEvent.touches.length > 1) {
        return;
    }

    // event.preventDefault();

    const touch = event.originalEvent.changedTouches[0];

    // Initialize the simulated mouse event using the touch event's coordinates
    const simulatedEvent = new MouseEvent(simulatedType, {
        bubbles: true,
        cancelable: true,
        view: window,
        detail: 1,
        screenX: touch.screenX,
        screenY: touch.screenY,
        clientX: touch.clientX,
        clientY: touch.clientY,
        ctrlKey: false,
        altKey: false,
        shiftKey: false,
        metaKey: false,
        button: 0,
        relatedTarget: null
    });

    // Dispatch the simulated event to the target element
    event.target.dispatchEvent(simulatedEvent);
}

$.widget('ui.sortable', $.ui.sortable, {
    /**
     * Handle the jQuery UI widget's touchstart events
     * @param {Object} event The widget element's touchstart event
     */
    _touchStart(event) {
        // Prevents interactions from starting on specified elements.
        // options.cancel is selector like: 'a, input, .btn, select'
        const elIsCancel = typeof this.options.cancel === 'string' && event.target.nodeName
            ? $(event.target).closest(this.options.cancel).length
            : false;

        // Ignore the event if another widget is already being handled
        if (touchHandled || elIsCancel || !this._mouseCapture(event.originalEvent.changedTouches[0])) {
            return;
        }

        if (!$(event.target).is(this.options.touchElements)) {
            event.stopPropagation();
            event.preventDefault();
        }

        // Set the flag to prevent other widgets from inheriting the touch event
        touchHandled = true;

        // Simulate the mousedown event
        simulateMouseEvent(event, 'mousedown');
    },

    _getCreateOptions() {
        const options = this._super() || {};

        // Allows trigger 'touchstart' and 'touchend' events on elements matching the selector while sorting
        options.touchElements = 'a, a *, button, button *';

        return options;
    },

    /**
     * Handle the jQuery UI widget's touchmove events
     * @param {Object} event The document's touchmove event
     */
    _touchMove(event) {
        // Ignore event if not handled
        if (!touchHandled) {
            return;
        }

        event.preventDefault();

        // Simulate the mousemove event
        simulateMouseEvent(event, 'mousemove');
    },

    /**
     * Handle the jQuery UI widget's touchend events
     * @param {Object} event The document's touchend event
     */
    _touchEnd(event) {
        // Ignore event if not handled
        if (!touchHandled) {
            return;
        }

        if (!$(event.target).is(this.options.touchElements)) {
            event.stopPropagation();
            event.preventDefault();
        }

        // Simulate the mouseup event

        simulateMouseEvent(event, 'mouseup');
        // Unset the flag to allow other widgets to inherit the touch event
        touchHandled = false;

        return true;
    },

    _mouseStart(...args) {
        this._trigger('beforePick', args[0], this._uiHash());
        return this._superApply(args);
    },

    _clear(...args) {
        this._trigger('beforeDrop', args[0], this._uiHash());
        return this._superApply(args);
    },

    /**
     * Method _mouseInit extends $.ui.mouse widget with bound touch event handlers that
     * translate touch events to mouse events and pass them to the widget's
     * original mouse event handling methods.
     */
    _mouseInit(...args) {
        // Delegate the touch handlers to the widget's element
        const handlers = {
            touchstart: this._touchStart.bind(this),
            touchmove: this._touchMove.bind(this),
            touchend: this._touchEnd.bind(this)
        };

        Object.keys(handlers).forEach(function(eventName) {
            handlers[eventName + '.' + this.widgetName] = handlers[eventName];
            delete handlers[eventName];
        }.bind(this));

        this.element.on(handlers);

        this._touchMoved = false;

        this._superApply(args);
    },

    /**
     * Faster and rough handle class setting method
     */
    _setHandleClassName() {
        this._removeClass(this.element.find('.ui-sortable-handle'), 'ui-sortable-handle');

        this._addClass(
            this.options.handle ? this.element.find(this.options.handle) : $($.map(this.items, function(item) {
                return item.item.get(0);
            })),
            'ui-sortable-handle'
        );
    }
});
