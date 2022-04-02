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

    processColumnsAndListenEvents: function() {
        this.processColumns();
    },

    processColumns: function() {
        const {rowClickAction} = this.main;

        if (rowClickAction && rowClickAction.prototype.link) {
            this.clickRowActionLink = rowClickAction.prototype.type === 'navigate'
                ? rowClickAction.prototype.link
                : false;
        }

        this.main.columns.each(this.patchCellConstructor.bind(this));
    },

    patchCellConstructor: function(column) {
        const Cell = column.get('cell');
        const clickRowActionLink = this.clickRowActionLink;

        const extended = Cell.extend({
            rowUrl: null,

            delegateEvents() {
                Cell.__super__.delegateEvents.call(this);
                this.rowUrl = clickRowActionLink ? this.model.get(clickRowActionLink) : false;

                if (this.rowUrl) {
                    if (!isTouchDevice()) {
                        this.$el.on(`mouseenter${this.eventNamespace()}`, this.createCellLinkView.bind(this));
                        this.$el.on(`mouseleave${this.eventNamespace()}`, this.destroyCellLinkView.bind(this));
                    } else {
                        this.createCellLinkView = _.debounce(this.createCellLinkView.bind(this), 50);
                        this.destroyCellLinkView = _.debounce(this.destroyCellLinkView.bind(this), 50);
                        this.$el.on(`touchstart${this.eventNamespace()}`, this.createCellLinkView.bind(this));
                        this.$el.on(
                            `touchend${this.eventNamespace()} touchcancel${this.eventNamespace()}`,
                            this.destroyCellLinkView.bind(this)
                        );
                    }
                    this.listenTo(this, 'before-enter-edit-mode', this.disposeCellLink.bind(this));
                }
            },

            isSkipRowClick() {
                return !_.isUndefined(this.skipRowClick) && this.skipRowClick;
            },

            createCellLinkView(event) {
                if (this.inEditMode() || (event.type === 'touchstart' && event.target.tagName === 'A')) {
                    return;
                }

                this.subview('cell-link', new CellLinkView({
                    container: this.$el,
                    url: this.rowUrl
                }));
            },

            destroyCellLinkView() {
                this.disposeCellLink();
            },

            inEditMode() {
                return this.$el.hasClass('edit-mode');
            },

            disposeCellLink() {
                if (this.subview('cell-link')) {
                    this.subview('cell-link').dispose();
                }
            }
        });

        column.set({
            cell: extended,
            oldCell: Cell
        });
    }
});

export default CellLinksPlugin;
