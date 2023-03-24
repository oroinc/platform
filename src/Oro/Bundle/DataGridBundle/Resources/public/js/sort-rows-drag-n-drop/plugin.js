import $ from 'jquery';
import {isEqual, difference} from 'underscore';
import __ from 'orotranslation/js/translator';
import {isMobile} from 'underscore';
import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import helperTemplate from 'tpl-loader!orodatagrid/templates/sort-rows-drag-n-drop/helper.html';
import cancelHintTemplate from 'tpl-loader!orodatagrid/templates/sort-rows-drag-n-drop/cancel-hint.html';
import SelectionStateHintView from 'orodatagrid/js/sort-rows-drag-n-drop/selection-state-hint-view';
import DropZoneMenuView from 'orodatagrid/js/sort-rows-drag-n-drop/drop-zone-menu-view';
import routing from 'routing';

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
    finishedClass: 'drag-n-drop-finished',

    /**
     * @property {string}
     */
    cursorOutClass: 'drag-n-drop-cursor-out',

    /**
     * @property {string}
     */
    dropFromDropZoneClass: 'drag-n-drop-from-drop-zone',

    /**
     * @property {Function}
     */
    helperTemplate,

    /**
     * @property {Function}
     */
    cancelHintTemplate,

    /**
     * @property {Object}
     */
    defaultOptions: {
        renderDropZonesMenu: false,
        highlightSortedItems: true,
        allowSelectMultiple: true,
        thinRowPlaceholder: true
    },

    /**
     * @property {number}
     */
    ORDER_STEP: 10,

    /**
     * @property {number}
     */
    ANIMATION_TIMEOUT: 500,

    /**
     * @property {number}
     */
    TABLE_BOUNDARY_CLEARANCE: 28,

    SORTABLE_DEFAULTS: {
        cursor: 'move',
        placeholder: 'sorting-placeholder',
        forceHelperSize: false,
        items: '.grid-row',
        tolerance: 'pointer'
    },

    SEPARATOR_ROW_SELECTOR: '.draggable-separator',

    SEPARATOR_PLACEHOLDER_SELECTOR: '.separator',

    /**
     * @inheritdoc
     */
    constructor: function SortRowsDragNDropPlugin(main, manager, options) {
        options = Object.assign({}, this.defaultOptions, options);
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

        if (!this.options.formName) {
            throw new Error('Options "formName" is required');
        }

        if (!this.options.route) {
            throw new Error('Options "route" is required');
        }

        if (!this.options.route_parameters) {
            throw new Error('Options "route_parameters" is required');
        }

        this._collectSortOrderData();

        const {eventBus: externalEventBus} = this.options;
        if (externalEventBus) {
            // proxy all own events to externalEventBus, if it is provided through options
            this.listenTo(this, 'all', (eventName, ...args) => externalEventBus.trigger(eventName, ...args));
            externalEventBus.trigger('init', this);
        }
        this.listenTo(this.main, {
            disable: this.disable,
            enable: this.enable
        });
        this.listenTo(this.main.collection, 'remove', this._checkIfEmptyCollection);
        this.listenTo(this, 'before:unsetModelsAttr', this._runAnimation);

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

        const {models} = this.main.collection;
        // make sure all models in the collection have unique ascending values
        for (let index = 1; index < models.length; index++) {
            const sortOrder = models[index].get('_sortOrder');
            if (typeof sortOrder !== 'number') {
                // loop went beyond sorted model, nothing to update after it
                break;
            }
            const prevSortOrder = models[index - 1].get('_sortOrder');
            if (sortOrder <= prevSortOrder) {
                // update sort order with minimal step to reduce number of changed models
                models[index].set('_sortOrder', prevSortOrder + 1);
            }
        }

        this.main.collection.sort();

        this.main.$el.addClass(this.enabledClass);
        this.main.$el.toggleClass('drag-n-drop-highlight-sorted', this.options.highlightSortedItems);

        this.selectionStateHintView = new SelectionStateHintView({
            autoRender: true,
            referenceEl: this.$rootEl,
            collection: this.main.collection
        });
        this.selectionStateHintView.$el.insertAfter(this.main.$el.find('[role="grid"]'));
        this.listenTo(this.selectionStateHintView, 'reset',
            this._unsetModelsAttr.bind(this, '_selected', {ignoreAnimation: true}));

        this.delegateEvents();
        if (this.options.renderDropZonesMenu) {
            this.initDroppableZones();
        }
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
        this.main.$el.removeClass('drag-n-drop-highlight-sorted');
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
     * Listen to document events
     */
    observeMouse() {
        $(document).on(`mousemove.mouseObserver${this.eventNamespace()}`, this.onDocumentMouseMove.bind(this));
    },

    /**
     * Stop to listen to document events
     */
    stopObservingMouse() {
        $(document).off(`.mouseObserver${this.eventNamespace()}`);
    },

    /**
     * Handler on mouse move
     * @param {Event} e
     */
    onDocumentMouseMove(e) {
        // There may be a case when the event will be captured by the system or browser extension like screenshot maker.
        // As result, that event will fire later.
        if (this.disposed || !this.main.$el.hasClass(this.startDragClass)) {
            return;
        }

        const instance = this.main.body.$el.sortable('instance');
        const cursorOut = this.isCursorOutOfGridEl(e);

        if (instance._cancelSorting !== cursorOut) {
            if (cursorOut) {
                this.main.$el.addClass(this.cursorOutClass);
                this.renderCancelHint();
            } else {
                this.removeCancelHint();
                this.main.$el.removeClass(this.cursorOutClass);
            }

            this.trigger('mousemove:out', e, cursorOut);
            this.extendTableHeight();
        }
        instance._cancelSorting = cursorOut;
    },

    /**
     * Determines if a sortable placeholder does not have place to move and cursor moves out of grid
     * @param {Event} e
     * @returns {boolean}
     */
    isCursorOutOfGridEl(e) {
        const {pageX, pageY} = e;
        const {placeholder, currentItem} = this.main.body.$el.sortable('instance');
        const gridReact = this.main.el.getBoundingClientRect();

        if (
            pageX + this.TABLE_BOUNDARY_CLEARANCE < gridReact.left ||
            pageX - this.TABLE_BOUNDARY_CLEARANCE > gridReact.right
        ) {
            return true;
        }

        if (
            (
                currentItem.is(':first-child') ||
                placeholder.is(':first-child') ||
                currentItem.is(':last-child') ||
                placeholder.is(':last-child')
            ) && (pageY < gridReact.top || pageY - this.TABLE_BOUNDARY_CLEARANCE > gridReact.bottom)
        ) {
            return true;
        }

        return false;
    },

    /**
     * Handler for datagrid row mousedown
     * @param {Event} e
     */
    onMouseDown(e) {
        const currentItem = $(e.currentTarget);
        if (currentItem.is(this.SEPARATOR_ROW_SELECTOR) || !this.options.allowSelectMultiple) {
            // ignore selection if it's a separator row
        } else if (e.shiftKey && this._lastClickedIndex !== void 0) {
            let from = this._lastClickedIndex;
            let to = currentItem.index();

            if (from > to) {
                [from, to] = [to, from];
            }

            this._unsetModelsAttr('_selected', {ignoreAnimation: true});
            const selectedModels = this.main.collection.slice(from, to + 1);

            selectedModels.forEach(model => {
                const index = this.main.collection.indexOf(model);
                const selected = index >= from && index <= to;
                if (!model.isSeparator()) {
                    model.set('_selected', selected);
                }
            });
        } else if (e.ctrlKey || e.metaKey) {
            const modelId = currentItem.data('modelId');
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
        const currentItem = $(e.currentTarget);
        if (e.shiftKey || e.ctrlKey || e.metaKey || currentItem.is(this.SEPARATOR_ROW_SELECTOR)) {
            return;
        }

        const modelId = currentItem.data('modelId');
        const model = this.main.collection.get(modelId);

        this._unsetModelsAttr('_selected', {ignoreAnimation: true});
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
                dropHandler: this.moveSelectedToTop.bind(this)
            },
            toBottom: {
                title: __('oro.datagrid.drop_zones.mode_to_bottom'),
                order: 20,
                dropHandler: this.moveSelectedToBottom.bind(this)
            },
            removeSortOrder: {
                title: __('oro.datagrid.drop_zones.remove_sort_order'),
                order: 30,
                dropHandler: this.removeSortOrderForSelected.bind(this),
                enabled: () => {
                    return this.main.collection.filter(model => {
                        return model.get('_selected') && model.get('_sortOrder') !== void 0;
                    }).length > 0;
                }
            }
        };

        this.dropZoneMenuView = new DropZoneMenuView({
            autoRender: true,
            datagrid: this.main,
            dropZones: $.extend(true, {}, droppableZones, this.options.dropZones || {})
        });

        this.listenTo(this, {
            'sortable:beforePick': (e, ui) => {
                if (ui.item.is(this.SEPARATOR_ROW_SELECTOR)) {
                    return;
                }

                this.dropZoneMenuView
                    .updateShiftProp(this._cursorOnRightSide(e.pageX))
                    .show();
            },
            'sortable:stop': this.dropZoneMenuView.hide.bind(this.dropZoneMenuView)
        });
        this.dropZoneMenuView.$el.insertBefore(this.main.$el.find('[role="grid"]'));

        // Event "dropout" and "dropover" can be fired in different sequences
        let droppableEl = null;
        this.listenTo(this.dropZoneMenuView, {
            dropout: (e, ui) => {
                if (e.target.isSameNode(droppableEl)) {
                    this.main.$el.find(`.${this.SORTABLE_DEFAULTS.placeholder}`).show();
                    this.extendTableHeight();
                }
            },
            dropover: (e, ui) => {
                droppableEl = e.target;
                this.main.$el.find(`.${this.SORTABLE_DEFAULTS.placeholder}`).hide();
                this.extendTableHeight();
            },
            drop() {
                this.main.$el.addClass(this.dropFromDropZoneClass);
            },
            dropdone: () => {
                const {currentItem} = this.main.body.$el.sortable('instance');

                this._updateSortOrder();
                this.main.collection.sort();
                this.main.$el.removeClass(this.dropFromDropZoneClass);
                // jquery forces to change visibility of this element
                currentItem.css('display', '');
                this._saveChanges();
                this._unsetModelsAttr('_selected');
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
    },

    moveSelectedToBottom() {
        const models = this.main.collection.filter('_selected');
        const modelsIds = models.map(model => model.id);
        const $rows = this.main.body.$('tr').filter((i, el) => {
            return modelsIds.includes($(el).data('modelId'));
        });
        const $separator = this.main.body.$el.find(this.SEPARATOR_ROW_SELECTOR);

        if ($separator.length) {
            $separator.before($rows.detach());
        } else {
            this.main.body.$el.append($rows.detach());
        }
    },

    removeSortOrderForSelected() {
        const models = this.main.collection.filter('_selected');

        models.forEach(model => model.set('_sortOrder', null));
        this.main.collection.sort();
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
            this._unsetModelsAttr('_selected', {ignoreAnimation: true});
            this.main.body.$el.sortable('option', 'cursorAt', false);
        } else {
            this._selectRowBeforeDragStart(e, ui.item);
            this._adjustHelperPosition(e, ui);
        }

        const {cursor} = this.SORTABLE_DEFAULTS;
        this.main.body.$el.sortable('option', 'cursor', !isSeparator ? cursor : 'row-resize');
        this.main.body.$el.sortable('option', 'forcePlaceholderSize', !this.options.thinRowPlaceholder || isSeparator);
        this.trigger('sortable:beforePick', e, ui);
        this.extendTableHeight();
    },

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    onStartSortable(e, ui) {
        const isSeparator = ui.item.is(this.SEPARATOR_ROW_SELECTOR);
        ui.placeholder.toggleClass('separator', isSeparator);
        ui.placeholder.toggleClass('row-placeholder', !isSeparator);
        this.main.$el.addClass(this.startDragClass);
        this.main.$el.toggleClass('with-thin-row-placeholder', this.options.thinRowPlaceholder);
        this.trigger('sortable:start', e, ui);
        this.observeMouse();
    },

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    beforeItemDrop(e, ui) {
        if (this._currentItemClone) {
            this._currentItemClone.remove();
            delete this._currentItemClone;
        }
        this.main.$el.removeClass(this.startDragClass);
        this.trigger('sortable:beforeDrop', e, ui);
    },

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    onStopSortable(e, ui) {
        const {_cancelSorting} = $(e.target).sortable('instance');

        this.trigger('sortable:stop', e, ui);
        this.stopObservingMouse();
        this.restoreTableHeight();
        this.removeCancelHint();
        // Drop was done in other place like droppable zone
        if (ui.item.data('dropDone')) {
            ui.item.data('dropDone', null);
            return;
        }

        this._unsetModelsAttr('_overturned');
        if (e.originalEvent.target === null) {
            // event was canceled during drag action
            this.onStopSortableCancel(e, ui);
        } else if (_cancelSorting) {
            $(e.target).sortable('cancel');
            this.onStopSortableCancel(e, ui);
        } else {
            this.onStopSortableSuccess(e, ui);
        }

        // there might be a case when a system keys shortcut was pressed (e.g. "Meta+Shift+4") during drag action.
        // Sortable is disabled on modifier keyDown, but not enabled on keyUp,
        // because keyboard event on shortcut press is captured by system and not propagated to browser
        this.enableSortable();
    },

    /**
     * Handles placeholder position change and mark models as overturned once they switched their place with the separator
     *
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
                model.set('_overturned', modelIndex !== rowIndex);
            });
    },

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    onStopSortableSuccess(e, ui) {
        this._completeDOMUpdate(ui);

        this._updateSortOrder();
        this.main.collection.sort();
        this._saveChanges();
        this._unsetModelsAttr('_selected');
    },

    /**
     * Extends the height of datagrid's body to have possibility to use Drag Zone actions in case it is narrow
     */
    extendTableHeight() {
        if (!this.dropZoneMenuView || this.dropZoneMenuView.$el.is(':hidden')) {
            return;
        }

        // Reset a previous value for correct calculation
        this.restoreTableHeight();
        const dropZoneMenHeight = this.dropZoneMenuView.$el.outerHeight();
        const bodyHeight = this.main.body.$el.outerHeight();

        if (dropZoneMenHeight > bodyHeight) {
            this.main.el.style.setProperty(
                '--sort-rows-drag-n-drop-extend-height',
                `${dropZoneMenHeight - bodyHeight}px`
            );
        }
    },

    /**
     * Restores rid of an adjusted height of datagrid's body
     */
    restoreTableHeight() {
        this.main.el.style.removeProperty('--sort-rows-drag-n-drop-extend-height');
    },

    renderCancelHint() {
        const $continer = this.main.$el.find('.scrollbar-is-visible, [role="grid"]').first();

        this._$cancelHint = $(cancelHintTemplate());
        this._$cancelHint.insertBefore($continer);
        this._$cancelHint.fadeIn('fast');
    },

    removeCancelHint() {
        if (!this._$cancelHint) {
            return;
        }

        this._$cancelHint.fadeOut('fast', () => {
            this._$cancelHint.remove();
            delete this._$cancelHint;
        });
    },

    /**
     * Find appropriate rows by mode's property and run animation for them
     * @param {string} attr
     * @param {Object} [options]
     * @protected
     */
    _runAnimation(attr = '_selected', options = {}) {
        if (options.ignoreAnimation) {
            return;
        }
        const rowByModeCid = Object.fromEntries(this.main.body.rows.map(row => [row.model.cid, row]));
        this.main.collection.filter(attr)
            .map(model => rowByModeCid[model.cid])
            .forEach(row => row.$el.addClassTemporarily('animate', this.ANIMATION_TIMEOUT));
        this.main.$el.addClassTemporarily(this.finishedClass, this.ANIMATION_TIMEOUT);
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
            .sort((a, b) => a - b);

        changedModels
            .forEach((model, index) => {
                if (!model.isSeparator()) {
                    model.set('_sortOrder', sortOrderList[index]);
                }
            });
    },

    /**
     * Check if there are no other models in collection except separator -- remove separator as well
     * @protected
     */
    _checkIfEmptyCollection() {
        const separatorOnly = this.main.collection.every(model => model.isSeparator());
        if (separatorOnly) {
            const separatorModel = this.main.collection.get('separator');
            this.main.collection.remove(separatorModel, {alreadySynced: true});
        }
    },

    /**
     * Make a snapshot of sortOrder data
     * @protected
     */
    _collectSortOrderData() {
        this._sortOrderData = Object.fromEntries(
            this.main.collection
                .filter(model => !model.isSeparator())
                .map(model => [model.get('id'), model.sortOrderBackendFormatData()])
        );
    },

    _saveChanges() {
        const sortOrderData = this.main.collection
            .filter(model => {
                const sortOrder = this._sortOrderData[model.get('id')];
                return !model.isSeparator() && !isEqual(sortOrder, model.sortOrderBackendFormatData());
            })
            .map(model => [model.get('id'), model.sortOrderBackendFormatData()]);

        const removeProducts = difference(
            Object.keys(this._sortOrderData),
            this.main.collection.map(model => model.get('id'))
        );

        if (!sortOrderData.length && !removeProducts.length) {
            // no changed models -- nothing to save
            return;
        }

        if (!this._activeAjaxActions) {
            this._activeAjaxActions = 0;
        }

        const {route, route_parameters: params, formName} = this.options;
        const xhr = $.ajax({
            url: routing.generate(route, params),
            type: 'PUT',
            data: {
                [formName]: {
                    sortOrder: JSON.stringify(Object.fromEntries(sortOrderData)),
                    removeProducts: removeProducts.join(',')
                }
            },
            // to prevent main application loading bar from been shown
            global: false,
            beforeSend: () => {
                this.$rootEl.trigger('ajaxStart');
                this._activeAjaxActions++;
            },
            complete: () => {
                if (this.disposed) {
                    return;
                }
                this._activeAjaxActions--;
                if (this._activeAjaxActions === 0) {
                    this.$rootEl.trigger('ajaxComplete');
                }
            }
        });

        this.trigger('saveChanges', xhr, {
            sortOrder: sortOrderData,
            removeProducts
        });

        this._collectSortOrderData();
    },

    /**
     * @param {Event} e
     * @param {Object} ui
     */
    onStopSortableCancel(e, ui) {},

    /**
     * @param {Event} e
     * @param {jQuery|HTMLElement} currentItem
     * @returns {jQuery|HTMLElement}
     * @protected
     */
    _createSortableHelper(e, currentItem) {
        const isSeparator = currentItem.is(this.SEPARATOR_ROW_SELECTOR);
        const templateData = {
            isSeparator,
            data: this._getSelectedModelsData(),
            hasSortOrder: currentItem.is('.row-has-sort-order')
        };

        if (this.options.thinRowPlaceholder && !isSeparator) {
            this._currentItemClone = currentItem.clone().insertAfter(currentItem);
        }

        const $helper = $(this.helperTemplate(templateData));

        if (this.options.renderDropZonesMenu && !isMobile() && !isSeparator) {
            $helper.css('width', currentItem.width() / 2);
        }

        return $helper;
    },

    /**
     * @param {Event} e
     * @param {Object} ui
     * @protected
     */
    _adjustHelperPosition(e, ui) {
        if (isMobile()) {
            return;
        }

        const tableReact = e.target.getBoundingClientRect();
        const itemReact = ui.item[0].getBoundingClientRect();
        const cssTop = e.pageY - itemReact.top;
        let cssLeft = e.pageX - tableReact.left;

        if (this._cursorOnRightSide(e.pageX)) {
            const helperWidth = ui.item.width() / 2;
            const delta = $(e.target).width() + tableReact.left - e.pageX;

            cssLeft = helperWidth - delta;
        }

        $(e.target).sortable('option', 'cursorAt', {
            top: cssTop,
            left: Math.max(cssLeft, 0)
        });
    },

    /**
     * Marks row's model as "_selected"
     * @param {Event} e
     * @param {jQuery|HTMLElement} $el
     * @protected
     */
    _selectRowBeforeDragStart(e, $el) {
        const model = this.main.collection.models[$el.index()];
        model.set('_selected', true);
    },

    /**
     * Gets data of selected models
     * @returns {Array}
     * @protected
     */
    _getSelectedModelsData() {
        return this.main.collection
            .filter('_selected')
            .map(model => model.toJSON());
    },

    /**
     * Unset specific model's attribute
     * @param {string} attr
     * @param {Object} [options]
     * @protected
     */
    _unsetModelsAttr(attr, options = {}) {
        if (!options.silent) {
            this.trigger('before:unsetModelsAttr', attr, options);
        }

        this.main.collection.forEach(model => model.unset(attr, options));
    },

    /**
     * Defines if a cursor is on the right side of the window
     * @param {number} x
     * @returns {boolean}
     * @protected
     */
    _cursorOnRightSide(x) {
        return x > (window.innerWidth / 2);
    }
});

export default SortRowsDragNDropPlugin;
