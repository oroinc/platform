import {sortBy} from 'underscore';
import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orodatagrid/templates/sort-rows-drag-n-drop/drop-zone-menu.html';

import scrollHelper from 'oroui/js/tools/scroll-helper';

import 'jquery-ui/widgets/droppable';

const DropZoneMenuView = BaseView.extend({
    /**
     * @inheritdoc
     */
    template: template,

    /**
     * @inheritdoc
     */
    className: 'drop-zones-menu',

    /**
     * @inheritdoc
     */
    listen: {
        drop: 'hide'
    },

    /**
     * Defines to shift its position
     * @property {boolean}
     */
    shiftStart: false,

    OFFSET: 20,

    /**
     * @inheritdoc
     */
    constructor: function DropZoneMenuView(options) {
        DropZoneMenuView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    preinitialize(options) {
        this.datagrid = options.datagrid;

        if (this.datagrid === void 0) {
            throw new Error('Option "datagrid" is required for DropZoneMenuView');
        }
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        this.zones = options.dropZones;

        DropZoneMenuView.__super__.initialize.call(this, options);
    },

    /**
     * @inheritdoc
     */
    delegateEvents(events) {
        DropZoneMenuView.__super__.delegateEvents.call(this, events);
        this.datagrid.body.$el.on(`mousemove${this.eventNamespace()}`, e => {
            this.mouseY = e.clientY;
        });
    },

    /**
     * @inheritdoc
     */
    undelegateEvents() {
        if (this.$el) {
            // this.$el might be not set yet
            this.datagrid.body.$el.off(this.eventNamespace());
        }
        DropZoneMenuView.__super__.undelegateEvents.call(this);
    },

    /**
     * @inheritdoc
     */
    render() {
        DropZoneMenuView.__super__.render.call(this);

        this.updatePosition();
        this.initDroppable();
        return this;
    },

    /**
     * @inheritdoc
     */
    getTemplateData: function() {
        const data = DropZoneMenuView.__super__.getTemplateData.call(this);

        const zones = Object.entries(this.zones)
            .map(([key, value]) => {
                value._type = key;
                return value;
            })
            .filter(zone => {
                if (typeof zone.enabled === 'function') {
                    return zone.enabled(this.datagrid);
                }
                return zone.enabled !== false;
            });

        data.zones = sortBy(zones, 'order');
        return data;
    },

    initDroppable() {
        if (this.disposed || this.$el.length === 0) {
            return;
        }

        this.destroyDroppable();

        this.$el.find('[data-zone]').each((i, el) => {
            const zoneType = el.getAttribute('data-zone');
            const {dropHandler} = this.zones[zoneType];

            $(el).droppable({
                activeClass: 'active',
                hoverClass: 'hover',
                tolerance: 'pointer',
                accept: `[data-page-component-name="${this.datagrid.name}"] .grid-row`,
                over: (e, ui) => {
                    this.trigger(e.type, e, ui);
                },
                out: (e, ui) => {
                    this.trigger(e.type, e, ui);
                },
                drop: (e, ui) => {
                    ui.draggable.data('dropDone', true);
                    this.trigger(e.type, e, ui);
                    if (typeof dropHandler !== 'function') {
                        return;
                    }

                    // do not break $.sortable working cycle, let it finish everything it needed
                    setTimeout(() => {
                        if (!this.disposed) {
                            const result = dropHandler(e, ui, this.datagrid);
                            this.trigger('dropdone', zoneType, result);
                        }
                    });
                }
            });
        });
    },

    destroyDroppable() {
        this.$el.find('[data-zone]').each((i, el) => {
            if ($(el).data('uiDroppable')) {
                $(el).droppable('destroy');
            }
        });
    },

    updatePosition() {
        if (!document.contains(this.el) || this.mouseY === void 0) {
            return;
        }

        const $referenceEl = this.$el.parents('.grid-scrollable-container, body');
        const hasOwnScroll = $referenceEl.is('.scrollbar-is-visible');
        const {left: referenceLeft} = $referenceEl.offset();
        let gridBodyTop = this.datagrid.body.$el.offset().top;
        let gridBodyBottom = gridBodyTop + this.datagrid.body.$el.height();

        if (hasOwnScroll) {
            gridBodyTop = $referenceEl.offset().top + this.datagrid.header.$el.height();
            gridBodyBottom = gridBodyTop + $referenceEl.height();
        }

        let cssTop = this.mouseY - this.$el.height() / 2;
        // Drop-zone cannot be placed above $referenceEl
        if (cssTop < gridBodyTop) {
            cssTop = gridBodyTop;
        // Drop-zone cannot be placed below $referenceEl
        } else if (this.mouseY + this.$el.height() > gridBodyBottom) {
            const delta = this.mouseY + this.$el.height() - gridBodyBottom;
            cssTop -= delta;
        }

        const referenceWidth = hasOwnScroll
            ? $referenceEl.width() - scrollHelper.scrollbarWidth() : $referenceEl.width();

        let cssLeft = referenceLeft + referenceWidth / 2 + this.OFFSET;
        if (this.shiftStart) {
            cssLeft = referenceLeft + referenceWidth / 2 - this.$el.width() - this.OFFSET;

            if (hasOwnScroll) {
                cssLeft -= scrollHelper.scrollbarWidth();
            }
        }

        this.$el.css({
            position: 'fixed',
            top: `${cssTop}px`,
            left: `${cssLeft}px`
        });
        return this;
    },

    updateShiftProp(value) {
        this.shiftStart = value;
        return this;
    },

    show() {
        this.render();
        this.$el.fadeIn('fast', () => {
            if (!this.disposed) {
                this.$el.addClass('show');
            }
        });
        return this;
    },

    hide() {
        this.$el.fadeOut('fast', () => {
            if (!this.disposed) {
                this.$el.removeClass('show');
            }
        });
        return this;
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.destroyDroppable();
        delete this.zones;
        DropZoneMenuView.__super__.dispose.call(this);
    }
});

export default DropZoneMenuView;
