import {sortBy} from 'underscore';
import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orodatagrid/templates/sort-rows-drag-n-drop/drop-zone-menu.html';

import 'jquery-ui/widgets/droppable';

const DropZoneMenuView = BaseView.extend({
    /**
     * @inheritdoc
     */
    template: template,

    /**
     * @inheritdoc
     */
    noWrap: true,

    /**
     * @inheritdoc
     */
    listen: {
        drop: 'hide'
    },

    /**
     * @inheritdoc
     */
    constructor: function DropZoneMenuView(options) {
        DropZoneMenuView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        this.datagrid = options.datagrid;

        if (this.datagrid === void 0) {
            throw new Error('Option "datagrid" is required for DropZoneMenuView');
        }
        this.zones = options.dropZones;

        DropZoneMenuView.__super__.initialize.call(this, options);
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
            .filter(zone => zone.enabled !== false);

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
            const runCallback = (e, ui) => {
                if (
                    this.zones[zoneType] &&
                    typeof e.type === 'string' &&
                    typeof this.zones[zoneType][`${e.type}Callback`] === 'function'
                ) {
                    // do not break $.sortable working cycle, let it finish everything it needed
                    setTimeout(() => {
                        this.zones[zoneType][`${e.type}Callback`](e, ui, this.datagrid);
                    }, 0);
                }
            };
            $(el).droppable({
                activeClass: 'active',
                hoverClass: 'hover',
                tolerance: 'pointer',
                accept: `[data-page-component-name="${this.datagrid.name}"] .grid-row`,
                over: (e, ui) => {
                    this.trigger(e.type, e, ui);
                    runCallback(e, ui);
                },
                out: (e, ui) => {
                    this.trigger(e.type, e, ui);
                    runCallback(e, ui);
                },
                drop: (e, ui) => {
                    this.trigger(e.type, e, ui);
                    runCallback(e, ui);
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
        const $referenceEl = this.datagrid.body.$el;
        const offset = $referenceEl.offset();
        const cssTop = Math.max(offset.top, (offset.top + $referenceEl.visibleHeight() / 2) - this.$el.height() / 2);
        const cssLeft = (offset.left + $referenceEl.innerWidth() - this.$el.width()) / 2;

        this.$el.css({
            position: 'fixed',
            top: `${cssTop}px`,
            left: `${cssLeft}px`
        });
    },

    show() {
        this.$el.fadeIn('fast', () => {
            if (!this.disposed) {
                this.$el.addClass('show');
            }
        });
        this.updatePosition();
    },

    hide() {
        this.$el.fadeOut('fast', () => {
            if (!this.disposed) {
                this.$el.removeClass('show');
            }
        });
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
