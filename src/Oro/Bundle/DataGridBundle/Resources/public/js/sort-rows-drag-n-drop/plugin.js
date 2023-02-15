import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import {isMobile} from 'underscore';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import helperTemplate from 'tpl-loader!orodatagrid/templates/sort-rows-drag-n-drop/helper.html';
import SelectionStateHintView from 'orodatagrid/js/sort-rows-drag-n-drop/selection-state-hint-view';
import DropZoneMenuViev from 'orodatagrid/js/sort-rows-drag-n-drop/drop-zone-menu-view';

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
     * @property {Function}
     */
    helperTemplate,

    /**
     * @property {number}
     */
    ORDER_STEP: 10,

    SORTABLE_DEFAULTS: {
        cursor: 'grabbing',
        placeholder: 'sorting-placeholder',
        forceHelperSize: false,
        forcePlaceholderSize: true,
        items: '.grid-row'
    },

    SEPARATOR_ROW_SELECTOR: '.draggable-separator',

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
        this.initDroppableZones();
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

        this.undelegateEvents();
        this.stopListening(this.selectionStateHintView);
        this.main.$el.removeClass(this.enabledClass);
        if (this.selectionStateHintView) {
            this.selectionStateHintView.dispose();
            delete this.selectionStateHintView;
        }

        this.destroyDroppableZones();
        this.destroySortable();

        // do not call parent disable method, it stops listeners and the plugin what be auto-enabled again
        this.enabled = false;
        this.trigger('disabled');
    },

    /**
     * @inheritdoc
     */
    delegateEvents() {
        SortRowsDragNDropPlugin.__super__.delegateEvents.call(this);

        this.main.body.$el.on(`mousedown${this.ownEventNamespace()}`, 'tr', this.onMouseDown.bind(this));
        this.main.body.$el.on(`click${this.ownEventNamespace()}`, 'tr', this.onClick.bind(this));
        const $rootElement = this.main.body.$el.closest('[role="dialog"]') || $(document.body);
        $rootElement
            .bindFirst(`keydown${this.ownEventNamespace()}`, this.onKeyDown.bind(this))
            .on(`keyup${this.ownEventNamespace()}`, this.onKeyUp.bind(this));
    },

    /**
     * @inheritdoc
     */
    undelegateEvents() {
        SortRowsDragNDropPlugin.__super__.undelegateEvents.call(this);
        this.main.body.$el.off(this.ownEventNamespace());
        const $rootElement = this.main.body.$el.closest('[role="dialog"]') || $(document.body);
        $rootElement.off(this.ownEventNamespace());
    },

    /**
     * Handler for datagrid row mousedown
     * @param {Event} e
     */
    onMouseDown(e) {
        const currentEL = $(e.currentTarget);
        if (currentEL.is(this.SEPARATOR_ROW_SELECTOR)) {
            // ignore selection if it's a separator row
        } else if (e.shiftKey && this._lastClickedIndex !== void 0) {
            let from = this._lastClickedIndex;
            let to = currentEL.index();

            if (from > to) {
                [from, to] = [to, from];
            }

            this._resetSelectedModels();
            const selectedModels = this.main.collection.slice(from, to + 1);

            selectedModels.forEach(model => {
                const index = this.main.collection.indexOf(model);
                const selected = index >= from && index <= to;
                model.set('_selected', selected);
            });
        } else if (e.ctrlKey || e.metaKey) {
            const modelId = currentEL.data('modelId');
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
        const currentEL = $(e.currentTarget);
        if (e.shiftKey || e.ctrlKey || e.metaKey || currentEL.is(this.SEPARATOR_ROW_SELECTOR)) {
            return;
        }

        const modelId = currentEL.data('modelId');
        const model = this.main.collection.get(modelId);

        this._resetSelectedModels();
        model.set('_selected', true);
        this._lastClickedIndex = this.main.collection.indexOf(model);
    },

    /**
     * Handler for body keydown
     * Prevent drag-n-drop if specific key is pressed
     * @param {Event} e
     */
    onKeyDown(e) {
        if (e.key === 'Escape' && this.main.$el.hasClass(this.startDragClass)) {
            this.main.body.$el.sortable('cancel');
            e.stopImmediatePropagation();
        }
        if (e.shiftKey || e.ctrlKey || e.metaKey) {
            this.disableSortable();
        }
    },

    /**
     * Handler for body keyup
     * @param {Event} e
     */
    onKeyUp(e) {
        this.enableSortable();
    },

    initDroppableZones() {
        if (isMobile()) {
            return;
        }
        const droppableZones = {
            toTop: {
                title: __('oro.datagrid.drop_zones.move_to_top'),
                order: 10,
                dropCallback: this.moveSelectedToTop.bind(this)
            },
            toBottom: {
                title: __('oro.datagrid.drop_zones.mode_to_bottom'),
                order: 20,
                dropCallback: this.moveSelectedToBottom.bind(this),
                enabled: this.main.body.$el.find(this.SEPARATOR_ROW_SELECTOR).length > 0
            },
            removeSortOrder: {
                title: __('oro.datagrid.drop_zones.remove_sort_order'),
                order: 30,
                dropCallback: this.removeSortOrderForSelected.bind(this)
            }
        };

        this.dropZoneMenuView = new DropZoneMenuViev({
            autoRender: true,
            datagrid: this.main,
            dropZones: $.extend(true, {}, droppableZones, this.options.dropZones || {})
        });
        this.dropZoneMenuView.listenTo(this, {
            'sortable:start': (e, ui) => {
                if (ui.item.is(this.SEPARATOR_ROW_SELECTOR)) {
                    return;
                }
                this.dropZoneMenuView.show();
            },
            'sortable:stop': this.dropZoneMenuView.hide.bind(this.dropZoneMenuView)
        });
        this.dropZoneMenuView.$el.insertBefore(this.main.$el);
        this.listenTo(this.dropZoneMenuView, {
            drop: () => this._dropDone = true,
            dropout: () => {
                delete this._dropDone;
            }
        });
    },

    destroyDroppableZones() {
        if (this.dropZoneMenuView) {
            this.stopListening(this.dropZoneMenuView);
            this.dropZoneMenuView.dispose();
            delete this.dropZoneMenuView;
        }
    },

    moveSelectedToTop() {
        const models = this.main.collection.filter('_selected');
        const modelsIds = models.map(model => model.id);

        const $rows = this.main.body.$('tr').filter((i, el) => {
            return modelsIds.includes($(el).data('modelId'));
        });
        this.main.body.$el.prepend($rows.detach());
        this._updateSortOrder();
        this.main.collection.sort();
        this._resetSelectedModels();
    },

    moveSelectedToBottom() {
        const models = this.main.collection.filter('_selected');
        const modelsIds = models.map(model => model.id);
        const $rows = this.main.body.$('tr').filter((i, el) => {
            return modelsIds.includes($(el).data('modelId'));
        });
        const $separator = this.main.body.$el.find(this.SEPARATOR_ROW_SELECTOR);

        $separator.before($rows.detach());

        this._updateSortOrder();
        this.main.collection.sort();
        this._resetSelectedModels();
    },

    removeSortOrderForSelected() {
        const models = this.main.collection.filter('_selected');

        models.forEach(model => model.set('_sortOrder', null));
        this.main.collection.sort();
        this._resetSelectedModels();
    },

    initSortable() {
        this.main.body.$el.sortable({
            ...this.SORTABLE_DEFAULTS,
            appendTo: this.main.$el.parent(),
            containment: this.main.el,
            helper: this._createSortableHelper.bind(this),
            beforePick: this.beforeItemPick.bind(this),
            start: this.onStartSortable.bind(this),
            beforeDrop: this.beforeItemDrop.bind(this),
            stop: this.onStopSortable.bind(this),
            change: this.onChangeSortable.bind(this)
        });

        document.getSelection().removeAllRanges();
        this.main.body.$el.disableSelection();
    },

    destroySortable() {
        if (this.main.body.$el.data('uiSortable')) {
            this.main.body.$el.enableSelection();
            this.main.body.$el.sortable('destroy');
        }
    },

    enableSortable() {
        if (
            this.main.body.$el.data('uiSortable') &&
            this.main.body.$el.sortable('widget').is('.ui-sortable-disabled') === true
        ) {
            this.main.body.$el.enableSelection();
            this.main.body.$el.sortable('enable');
        }
    },

    disableSortable() {
        if (
            this.main.body.$el.data('uiSortable') &&
            this.main.body.$el.sortable('widget').is('.ui-sortable-disabled') === false
        ) {
            this.main.body.$el.disableSelection();
            this.main.body.$el.sortable('disable');
        }
    },

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    beforeItemPick(e, ui) {
        const isSeparator = ui.item.is(this.SEPARATOR_ROW_SELECTOR);
        if (isSeparator) {
            this._resetSelectedModels();
        } else {
            this._selectRowBeforeDragStart(e, ui.item);
        }

        const {cursor, forcePlaceholderSize} = this.SORTABLE_DEFAULTS;
        this.main.body.$el.sortable('option', 'cursor', !isSeparator ? cursor : 'row-resize');
        this.main.body.$el.sortable('option', 'forcePlaceholderSize', !isSeparator ? forcePlaceholderSize : false);
        this.trigger('sortable:beforePick', e, ui);
    },

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    onStartSortable(e, ui) {
        const isSeparator = ui.item.is(this.SEPARATOR_ROW_SELECTOR);
        ui.placeholder.toggleClass('separator', isSeparator);
        this.main.$el.addClass(this.startDragClass);
        this.trigger('sortable:start', e, ui);
    },

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    beforeItemDrop(e, ui) {
        $(e.target).data('helperReact', ui.helper[0].getBoundingClientRect());
        this.main.$el.removeClass(this.startDragClass);
        this.trigger('sortable:beforeDrop', e, ui);
    },

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    onStopSortable(e, ui) {
        this.trigger('sortable:stop', e, ui);
        // Drop was done in other place like droppable zone
        if (this._dropDone) {
            return;
        }

        const helperReact = $(e.target).data('helperReact');
        const tableReact = e.target.getBoundingClientRect();
        const isDropOutOfTable = (
            helperReact.top > tableReact.bottom ||
            helperReact.right < tableReact.left ||
            helperReact.bottom < tableReact.top ||
            helperReact.left > tableReact.right
        );
        $(e.target).data('helperReact', null);
        this.main.collection.forEach(model => model.set('_changed', false));

        if (e.originalEvent.target === null) {
            // event was canceled during drag action
            this.onStopSortableCancel(e, ui);
        } else if (isDropOutOfTable && !ui.item.is(this.SEPARATOR_ROW_SELECTOR)) {
            $(e.target).sortable('cancel');
            this.onStopSortableCancel(e, ui);
        } else {
            this.onStopSortableSuccess(e, ui);
        }
    },

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    onChangeSortable(e, ui) {
        this.trigger('sortable:change');
        const isSeparator = ui.item.is(this.SEPARATOR_ROW_SELECTOR);
        if (!isSeparator) {
            return;
        }

        this.main.body.$('tr').not(ui.item).toArray()
            .forEach((el, rowIndex) => {
                if (el === ui.placeholder[0]) {
                    return;
                }
                const modelId = $(el).data('modelId');
                const model = this.main.collection.get(modelId);
                const modelIndex = this.main.collection.indexOf(model);
                model.set('_changed', modelIndex !== rowIndex);
            });
    },

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    onStopSortableSuccess(e, ui) {
        this._completeDOMUpdate(ui);

        {
            // @todo develop animation
            const selectedRows = this.main.collection.filter('_selected')
                .map(model => this.main.body.rows.find(row => row.model === model).el);
            $(selectedRows).addClass('info');
            setTimeout(() => $(selectedRows).removeClass('info'), 1000);
        }

        const changedModels = this._updateSortOrder();

        {
            // @todo develop animation
            const changedRows = changedModels
                .map(model => this.main.body.rows.find(row => row.model === model).el);
            $(changedRows).addClass('success');
            setTimeout(() => $(changedRows).removeClass('success'), 1000);
        }

        this.main.collection.sort();

        this._resetSelectedModels();
    },

    /**
     * Multiple rows were selected.
     * The last selected row is moved by sortable,
     * rest of rows have to be relocated beside of `ui.item` manually
     *
     * @param ui
     * @protected
     */
    _completeDOMUpdate(ui) {
        const {body: gridBody, collection} = this.main;
        const movedModelId = ui.item.data('modelId');
        const selectedModels = collection.filter('_selected');
        const movedModel = collection.get(movedModelId);

        if (!movedModel.isSeparator() && selectedModels.length > 1) {
            const movedModelIndex = selectedModels.indexOf(movedModel);
            const elems = selectedModels.map(model => gridBody.rows.find(row => row.model === model).el);
            ui.item.before(...elems.slice(0, movedModelIndex));
            ui.item.after(...elems.slice(movedModelIndex + 1));
        }
    },

    /**
     * Collect models with changed row index in DOM, and swaps current sortOrder values between those models
     *
     * @protected
     */
    _updateSortOrder() {
        const {collection} = this.main;

        let maxSortOrder = collection.reduce((maxValue, model) => {
            const sortOrder = model.isSeparator() ? void 0 : model.get('_sortOrder');
            return sortOrder > maxValue ? sortOrder : (maxValue ?? sortOrder);
        }, void 0) || 0;

        const changedModels = this.main.body.$('tr').toArray()
            .map((el, rowIndex) => {
                const modelId = $(el).data('modelId');
                const model = collection.get(modelId);
                const modelIndex = collection.indexOf(model);
                // separator has to be always within changed models to have reference of sorting edge
                return modelIndex !== rowIndex || model.isSeparator() ? model : null;
            })
            .filter(item => item);

        let withinSorted = true;
        const sortOrderList = changedModels
            .map(model => {
                let sortOrder;
                if (model.isSeparator()) {
                    withinSorted = false;
                    sortOrder = model.get('_sortOrder');
                } else if (withinSorted) {
                    sortOrder = model.get('_sortOrder') ?? (maxSortOrder = maxSortOrder + this.ORDER_STEP);
                }
                return sortOrder;
            })
            .sort();

        changedModels
            .forEach((model, index) => {
                if (!model.isSeparator()) {
                    model.set('_sortOrder', sortOrderList[index]);
                }
            });

        return changedModels;
    },

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
            isSeparator: currentEL.is(this.SEPARATOR_ROW_SELECTOR),
            data: this._getSelectedModelsData(),
            iconClasses: currentEL.find('.sort-icon').attr('class')
        };

        return $(this.helperTemplate(templateData));
    },

    /**
     * Marks row's model as "_selected"
     * @param {Event} e
     * @param {jQuery|HTMLElement} $el
     * @private
     */
    _selectRowBeforeDragStart(e, $el) {
        const model = this.main.collection.models[$el.index()];
        model.set('_selected', true);
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
            .forEach(model => model.set('_selected', false));
    }
});

export default SortRowsDragNDropPlugin;
