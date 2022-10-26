const swipeableModule = {
    /** @type {string} */
    direction: void 0,

    /** @type {{x: Number, y: Number}|null} */
    touchStartCoords: null,

    /** @type {{x: Number, y: Number}|null} */
    touchEndCoords: null,

    /** @type {Number} */
    elapsedTime: 0,

    /** @type {Number} */
    startTime: 0,

    minDistanceXAxis: 30,

    maxDistanceYAxis: 30,

    maxAllowedTime: 1000,

    start(event) {
        event = ('changedTouches' in event) ? event.changedTouches[0] : event;

        this.touchStartCoords = {
            x: event.pageX,
            y: event.pageY
        };

        this.startTime = new Date().getTime();

        event.target.dispatchEvent(
            this._customEventFactory('swipestart')
        );
    },

    move(event) {
        if (!this.touchStartCoords) {
            return;
        }

        event = ('changedTouches' in event) ? event.changedTouches[0] : event;

        const touchEndCoords = {
            x: event.pageX - this.touchStartCoords.x,
            y: event.pageY - this.touchStartCoords.y
        };

        event.target.dispatchEvent(
            this._customEventFactory('swipemove', this._collectOptions(touchEndCoords))
        );
    },

    end(event) {
        if (!this.touchStartCoords) {
            return;
        }

        event = ('changedTouches' in event) ? event.changedTouches[0] : event;

        this.touchEndCoords = {
            x: event.pageX - this.touchStartCoords.x,
            y: event.pageY - this.touchStartCoords.y
        };
        this.elapsedTime = new Date().getTime() - this.startTime;

        if (this.elapsedTime <= this.maxAllowedTime) {
            if (
                Math.abs(this.touchEndCoords.x) >= this.minDistanceXAxis &&
                Math.abs(this.touchEndCoords.y) <= this.maxDistanceYAxis
            ) {
                this.direction = this._getDirection(this.touchEndCoords.x);
                event.target.dispatchEvent(
                    this._customEventFactory(`swipe${this.direction}`, this.touchEndCoords)
                );
            }
        }

        event.target.dispatchEvent(
            this._customEventFactory('swipeend', this._collectOptions(this.touchEndCoords))
        );
    },

    _getDirection(coords) {
        return (coords < 0) ? 'left' : 'right';
    },

    _collectOptions(options) {
        return Object.assign(options, {
            direction: this._getDirection(options.x)
        });
    },

    _customEventFactory(eventName, detail = {}) {
        return new CustomEvent(eventName, {
            bubbles: true,
            detail
        });
    }
};

document.addEventListener('touchstart', swipeableModule.start.bind(swipeableModule));
document.addEventListener('touchmove', swipeableModule.move.bind(swipeableModule));
document.addEventListener('touchend', swipeableModule.end.bind(swipeableModule));
