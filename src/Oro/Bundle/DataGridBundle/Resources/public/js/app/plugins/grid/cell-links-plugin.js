import _ from 'underscore';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import CellLinkView from 'orodatagrid/js/datagrid/cell-link';
import {isTouchDevice} from 'oroui/js/tools';

const CellLinksPlugin = BasePlugin.extend({
    constructor: function CellLinksPlugin(...args) {
        CellLinksPlugin.__super__.constructor.apply(this, args);
    },

    enable() {
        if (this.main.columns) {
            this.processColumnsAndListenEvents();
        } else {
            this.listenToOnce(this.main, 'columns:ready', this.processColumnsAndListenEvents);
        }

        CellLinksPlugin.__super__.enable.call(this);
    },

    processColumnsAndListenEvents() {
        this.processColumns();
    },

    processColumns() {
        const {rowClickAction} = this.main;

        if (rowClickAction && rowClickAction.prototype.link) {
            this.clickRowActionLink = rowClickAction.prototype.type === 'navigate'
                ? rowClickAction.prototype.link
                : false;
        }

        this.main.columns.each(this.patchCellConstructor.bind(this));
    },

    patchCellConstructor(column) {
        const Cell = column.get('cell');
        const clickRowActionLink = this.clickRowActionLink;

        const PatchedCell = Cell.extend({
            rowUrl: null,

            main: this.main,

            delegateEvents() {
                PatchedCell.__super__.delegateEvents.call(this);
                this.rowUrl = clickRowActionLink ? this.model.get(clickRowActionLink) : false;

                if (this.rowUrl) {
                    const debouncedCreateCellLinkView = _.debounce(this.createCellLinkView.bind(this), 50);
                    const debouncedDestroyCellLinkView = _.debounce(this.destroyCellLinkView.bind(this), 50);

                    if (!isTouchDevice()) {
                        this.$el.off(`mouseenter${this.eventNamespace()} mouseleave${this.eventNamespace()}`);
                        this.$el.on(`mouseenter${this.eventNamespace()}`, debouncedCreateCellLinkView);
                        this.$el.on(`mouseleave${this.eventNamespace()}`, debouncedDestroyCellLinkView);
                    } else {
                        this.$el.off(`touchstart${this.eventNamespace()}
                            touchend${this.eventNamespace()}
                            touchcancel${this.eventNamespace()}`);
                        this.$el.on(`touchstart${this.eventNamespace()}`, this.createCellLinkView.bind(this));
                        this.$el.on(
                            `touchend${this.eventNamespace()} touchcancel${this.eventNamespace()}`,
                            this.destroyCellLinkView.bind(this)
                        );
                    }
                    this.listenTo(this, 'before-enter-edit-mode', this.destroyCellLinkView.bind(this));
                }
            },

            isSkipRowClick() {
                return !_.isUndefined(this.skipRowClick) && this.skipRowClick;
            },

            isSelectedText() {
                return window.getSelection().toString().length;
            },

            createCellLinkView(event) {
                if (this.inEditMode() ||
                    (event.type === 'touchstart' && event.target.tagName === 'A') ||
                    this.isSelectedText() ||
                    this.subview('cell-link')
                ) {
                    return;
                }

                if (isTouchDevice()) {
                    this.timeoutId = setTimeout(() => {
                        this.subview('cell-link', new CellLinkView({
                            container: this.$el,
                            url: this.rowUrl
                        }));
                    }, 100);
                } else {
                    this.subview('cell-link', new CellLinkView({
                        container: this.$el,
                        url: this.rowUrl
                    }));
                }
            },

            destroyCellLinkView() {
                if (this.timeoutId) {
                    clearTimeout(this.timeoutId);
                }
                this.disposeCellLink();
            },

            inEditMode() {
                return this.$el.hasClass('edit-mode');
            },

            disposeCellLink() {
                if (this.subview('cell-link') && !this.subview('cell-link').disposed) {
                    this.subview('cell-link').dispose();
                    this.removeSubview('cell-link');
                }
            }
        });

        column.set({
            cell: PatchedCell,
            oldCell: Cell
        });
    }
});

export default CellLinksPlugin;
