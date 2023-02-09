import $ from 'jquery';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import placeholderTemplate from 'tpl-loader!orodatagrid/templates/sort-rows-drag-n-drop/helper.html';
import SelectionStateHintView from 'orodatagrid/js/sort-rows-drag-n-drop/selection-state-hint-view';

import 'jquery-ui/widgets/sortable';
import 'jquery-ui/disable-selection';

const SortRowsDragNDropPlugin = BasePlugin.extend({
    /**
     * @property {string}
     */
    startDragClass: 'drag-n-drop-start',

    /**
     * @property {string}
     */
    enabledClass: 'drag-n-drop-enabled',

    /**
     * @property {string}
     */
    preventsSortingSelector: '[data-ignore-drag]',

    /**
     * @property {Function}
     */
    placeholderTemplate: placeholderTemplate,

    /**
     * @property {number}
     */
    ORDER_STEP: 10,

    /**
     * @inheritdoc
     */
    constructor: function SortRowsDragNDropPlugin(main, manager, options) {
        SortRowsDragNDropPlugin.__super__.constructor.call(this, main, manager, options);
    },

    /**
     * @inheritdoc
     */
    initialize(main, options) {
        this.$rootEl = options.$rootEL;

        if (this.$rootEl === void 0) {
            throw new Error('Option "$rootEl" is required');
        }

        this.listenTo(this.main, {
            disable: this.disable,
            enable: this.enable
        });

        this.listenToOnce(this.main, 'rendered', () => {
            // enable plugin only when the grid table is rendered
            this.enable();
        });

        SortRowsDragNDropPlugin.__super__.initialize.call(this, main, options);
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        this.disable();

        SortRowsDragNDropPlugin.__super__.dispose.call(this);
    },

    /**
     * @inheritdoc
     */
    enable() {
        if (
            (this.main.body.$el === void 0 || this.main.body.$el.length === 0)
        ) {
            // can not be enabled without body $el
            return;
        }

        this.main.$el.addClass(this.enabledClass);

        this.selectionStateHintView = new SelectionStateHintView({
            autoRender: true,
            referenceEl: this.$rootEl,
            collection: this.main.collection
        });
        this.selectionStateHintView.$el.insertAfter(this.$rootEl);
        this.listenTo(this.selectionStateHintView, 'reset', this._resetSelectedModels);

        this.delegateEvents();
        this.initSortable();

        SortRowsDragNDropPlugin.__super__.enable.call(this);
    },

    /**
     * @inheritdoc
     */
    disable() {
        if (!this.enabled) {
            return;
        }

        this.main.$el.removeClass(this.enabledClass);
        if (this.selectionStateHintView) {
            this.selectionStateHintView.dispose();
            delete this.selectionStateHintView;
        }
        this.destroySortable();
        this.undelegateEvents();

        this.stopListening(this.main.body);

        // do not call parent disable method, it stops listeners and the plugin what be auto-enabled again
        this.enabled = false;
        this.trigger('disabled');
    },

    /**
     * @inheritdoc
     */
    delegateEvents() {
        SortRowsDragNDropPlugin.__super__.delegateEvents.call(this);

        this.main.body.$el.on(`mousedown${this.ownEventNamespace()}`, 'tr', e => {
            if ($(e.target).is(this.preventsSortingSelector)) {
                return;
            }
            this.onMouseDown(e);
        });
        this.main.body.$el.on(`click${this.ownEventNamespace()}`, 'tr', e => {
            if ($(e.target).is(this.preventsSortingSelector)) {
                return;
            }
            this.onClick(e);
        });
    },

    /**
     * @inheritdoc
     */
    undelegateEvents() {
        SortRowsDragNDropPlugin.__super__.undelegateEvents.call(this);
        this.main.body.$el.off(this.ownEventNamespace());
    },

    /**
     * Handler for datagrid row mousedown
     * @param {Event} e
     */
    onMouseDown(e) {
        if (e.shiftKey && this._lastClickedIndex !== void 0) {
            let from = this._lastClickedIndex;
            let to = $(e.currentTarget).index();

            if (from > to) {
                [from, to] = [to, from];
            }

            this._resetSelectedModels();
            const selectedModels = this.main.collection.models.slice(from, to + 1);

            selectedModels.forEach(model => {
                const index = this.main.collection.indexOf(model);
                const selected = index >= from && index <= to;
                model.set('_selected', selected);
            });
        } else if (e.ctrlKey || e.metaKey) {
            const modelId = $(e.currentTarget).data('modelId');
            const model = this.main.collection.get(modelId);

            model.set('_selected', !model.get('_selected'));
            this._lastClickedIndex = this.main.collection.indexOf(model);
        }
    },

    /**
     * Handler for datagrid row click
     * @param {Event} e
     */
    onClick(e) {
        if (e.shiftKey || e.ctrlKey || e.metaKey) {
            return;
        }

        const modelId = $(e.currentTarget).data('modelId');
        const model = this.main.collection.get(modelId);

        this._resetSelectedModels();
        model.set('_selected', true);
        this._lastClickedIndex = this.main.collection.indexOf(model);
    },

    initSortable() {
        this.main.body.$el.sortable({
            appendTo: this.main.$el.parent(),
            cancel: this.preventsSortingSelector,
            containment: this.main.el,
            cursor: 'grabbing',
            placeholder: 'sorting-placeholder',
            forceHelperSize: false,
            forcePlaceholderSize: true,
            items: '.grid-row',
            helper: (e, currentEL) => {
                this._selectRowBeforeDragStart(e, currentEL);
                return this._createSortableHelper(e, currentEL);
            },
            start: (e, ui) => {
                this.main.$el.addClass(this.startDragClass);
                this.onStartSortable(e, ui);
            },
            beforeDrop: (e, ui) => {
                $(e.target).data('helperReact', ui.helper[0].getBoundingClientRect());
                this.main.$el.removeClass(this.startDragClass);
            },
            stop: (e, ui) => {
                const helperReact = $(e.target).data('helperReact');
                const tableReact = e.target.getBoundingClientRect();
                const isDropOutOfTable = (
                    helperReact.top > tableReact.bottom ||
                    helperReact.right < tableReact.left ||
                    helperReact.bottom < tableReact.top ||
                    helperReact.left > tableReact.right
                );
                $(e.target).data('helperReact', null);

                if (isDropOutOfTable) {
                    $(e.target).sortable('cancel');
                    this.onStopSortableCancel(e, ui);
                } else {
                    this.onStopSortableSuccess(e, ui);
                }
            }
        });

        const selection = document.getSelection();

        selection.removeAllRanges();
        this.main.body.$el.disableSelection();
    },

    destroySortable() {
        if (this.main.body.$el.data('uiSortable')) {
            this.main.body.$el.enableSelection();
            this.main.body.$el.sortable('destroy');
        }
    },

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    onStartSortable(e, ui) {},

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    onStopSortableSuccess(e, ui) {},

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    onStopSortableCancel(e, ui) {},

    /**
     * @param {Event} e
     * @param {jQuery|HTMLElement} currentEL
     * @returns {jQuery|HTMLElement}
     * @private
     */
    _createSortableHelper(e, currentEL) {
        const templateData = {
            data: this._getSelectedModelsData(),
            iconClasses: currentEL.find('.sort-icon').attr('class')
        };

        return $(this.placeholderTemplate(templateData));
    },

    /**
     * Marks row's model as "_selected"
     * @param {Event} e
     * @param {jQuery|HTMLElement} $el
     * @private
     */
    _selectRowBeforeDragStart(e, $el) {
        const model = this.main.collection.models[$el.index()];

        if (!model.get('_selected')) {
            model.set('_selected', true);
        }
    },

    /**
     * Gets data of selected models
     * @returns {Array}
     * @private
     */
    _getSelectedModelsData() {
        return this.main.collection
            .filter('_selected')
            .map(model => model.toJSON());
    },

    /**
     * @private
     */
    _resetSelectedModels() {
        this.main.collection
            .filter('_selected')
            .forEach(model => model.set('_selected', false));
    }
});

export default SortRowsDragNDropPlugin;
