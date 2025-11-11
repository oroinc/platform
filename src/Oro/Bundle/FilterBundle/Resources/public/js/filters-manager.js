import template from 'tpl-loader!orofilter/templates/filters-container.html';
import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';
import tools from 'oroui/js/tools';
import BaseView from 'oroui/js/app/views/base/view';
import filterWrapper from 'orofilter/js/datafilter-wrapper';
import FiltersStateView from 'orofilter/js/app/views/filters-state-view';
import persistentStorage from 'oroui/js/persistent-storage';
import FilterDialogWidget from 'orofilter/js/app/views/filter-dialog-widget';
import moduleConfig from 'module-config';
const config = moduleConfig(module.id);
const DEFAULT_STORAGE_KEY = 'filters-state';

/**
 * View that represents all grid filters
 *
 * @export  orofilter/js/filters-manager
 * @class   orofilter.FiltersManager
 * @extends BaseView
 *
 * @event updateList    on update of filter list
 * @event updateFilter  on update data of specific filter
 * @event disableFilter on disable specific filter
 */
const FiltersManager = BaseView.extend({
    /**
     * List of filter objects
     *
     * @type {Object}
     * @property
     */
    filters: null,

    /**
     * Template
     */
    template: template,

    /**
     * Mode of filters displaying
     *
     * @type {Integer}
     * @property
     */
    viewMode: NaN,

    /**
     *  Is used in template for render additional html
     * @property {String} 'collapse-mode' | 'toggle-mode'
     */
    renderMode: 'dropdown-mode',

    /**
     * Add filter button hint
     *
     * @property
     */
    addButtonHint: __('oro_datagrid.label_add_filter'),

    /**
     * Set title for dialog widget with filters
     *
     * @property
     */
    filterDialogTitle: __('oro.filter.dialog.filter_results'),

    /**
     * ImportExport button selector
     *
     * @property
     */
    buttonSelector: '.filter-list',

    /**
     * jQuery object that will be target for append multiselect dropdown menus
     *
     * @property
     */
    dropdownContainer: 'body',

    /**
     * Separate container selector where filter hint will placed
     *
     * @property {string}
     */
    outerHintContainer: void 0,

    /**
     * Flag for close previous open filters
     *
     * @property
     */
    autoClose: true,

    /**
     * Key that's used to fetch data about filters state view mode from persistent storage
     *
     * @property
     */
    storageKey: null,

    /** @property */
    events: {
        'click a[data-name="filters-dropdown"]': '_onDropdownToggle'
    },

    /**
     * @inheritdoc
     */
    listen: {
        'filters:update mediator': '_onChangeFilterSelect',
        'filters:reset mediator': '_onReset'
    },

    noWrap: true,

    /**
     * @inheritdoc
     */
    constructor: function FiltersManager(options) {
        FiltersManager.__super__.constructor.call(this, options);
    },

    /**
     * Initialize filter list options
     *
     * @param {Object} options
     * @param {Object} [options.filters]
     * @param {String} [options.addButtonHint]
     */
    initialize: function(options) {
        _.extend(this, _.pick(options,
            'addButtonHint', 'stateViewElement', 'template', 'renderMode',
            'autoClose', 'outerHintContainer', 'enableMultiselectWidget', 'multiselectParameters',
            'filterContainer'
        ));

        this.template = this.getTemplateFunction();
        this.filters = _.extend({}, options.filters);
        this.storageKey = options.filtersStateStorageKey || config.filtersStateStorageKey || DEFAULT_STORAGE_KEY;

        if (options.forcedViewMode) {
            this.viewMode = options.forcedViewMode;
        } else {
            this.viewMode = persistentStorage.getItem(this.storageKey);

            if (this.viewMode === null) {
                this.viewMode = options.defaultFiltersViewMode || FiltersManager.STATE_VIEW_MODE;
            }
        }

        const filterListeners = {
            update: this._onFilterUpdated,
            change: this._onFilterChanged,
            disable: this._onFilterDisabled,
            showCriteria: this._onFilterShowCriteria,
            update_criteria_label: this._resetHintContainer
        };

        if (tools.isMobile()) {
            const outsideActionEvents = 'click.' + this.cid + ' shown.bs.dropdown.' + this.cid;
            filterListeners.updateCriteriaClick = this._onUpdateCriteriaClick;
            $('body').on(outsideActionEvents, this._onOutsideActionEvent.bind(this));
        }

        _.each(this.filters, function(filter) {
            if (filter.wrappable) {
                Object.assign(filter, filterWrapper);
            }
            if (this.autoClose === false) {
                Object.assign(filter, {autoClose: this.autoClose});
            }
            this.listenTo(filter, filterListeners);
            filter.trigger('total-records-count-updated', this.collection.state.totalRecords);
        }, this);

        if (this.isFiltersStateViewNeeded(options)) {
            const filtersStateView = new FiltersStateView({
                el: options.filtersStateElement,
                filters: options.filters,
                useAnimationOnInit: options.useFiltersStateAnimationOnInit
            });

            this.subview('filters-state', filtersStateView);
            this.listenTo(filtersStateView, 'clicked', function() {
                this.setViewMode(FiltersManager.MANAGE_VIEW_MODE);

                const filter = Object.values(this.filters)
                    .find(filter => {
                        return filter.visible &&
                                filter.renderable &&
                                filter.getCriteriaSelector().attr('tabindex') !== '-1';
                    });

                if (filter && $.contains(filtersStateView.el, document.activeElement)) {
                    filter.getCriteriaSelector().trigger('focus');
                }
            });
        }

        FiltersManager.__super__.initialize.call(this, options);
    },

    hasFilters: function() {
        return !_.isEmpty(this.filters);
    },

    /**
     * @inheritdoc
     */
    delegateListeners: function() {
        if (!_.isEmpty(this.filters)) {
            this.listenTo(mediator, 'datagrid:metadata-loaded', this.updateFilters);
        }

        return FiltersManager.__super__.delegateListeners.call(this);
    },

    /**
     * @param {orodatagrid.datagrid.Grid} grid
     */
    updateFilters: function(grid) {
        _.each(grid.metadata.filters, function(metadata) {
            const filter = this.filters[metadata.name];
            if (filter) {
                filter.setRenderMode(this.renderMode);
                filter.trigger('total-records-count-updated', this.collection.state.totalRecords);
                filter.trigger('metadata-loaded', metadata);
            }
        }, this);

        this.checkFiltersVisibility();
    },

    checkFiltersVisibility: function() {
        _.each(this.filters, filter => {
            if (filter.visible && filter.renderable) {
                this._renderFilter(filter).show();
            } else if (!filter.visible) {
                filter.hide();
            }
        });
    },

    /**
     * @param {object} options
     * @returns {boolean}
     */
    isFiltersStateViewNeeded: function(options) {
        return 'filtersStateElement' in options;
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }
        $('body').off('.' + this.cid);
        _.each(this.filters, function(filter) {
            filter.dispose();
        });
        delete this.filters;
        FiltersManager.__super__.dispose.call(this);
    },

    /**
     * Triggers when filter is updated
     *
     * @param {oro.filter.AbstractFilter} filter
     * @protected
     */
    _onFilterUpdated: function(filter) {
        this._resetHintContainer();
        this.trigger('updateFilter', filter);
        this._publishCountSelectedFilters();
    },

    /**
     * Triggers when filter DOM Value is changed
     *
     * @param {oro.filter.AbstractFilter} filter
     * @protected
     */
    _onFilterChanged: function() {
        this._publishCountSelectedFilters();
        this._publishCountChangedFilters();
    },

    /**
     * Triggers when filter is disabled
     *
     * @param {oro.filter.AbstractFilter} filter
     * @protected
     */
    _onFilterDisabled: function(filter) {
        this.trigger('disableFilter', filter);
        this.disableFilter(filter);
        this.trigger('afterDisableFilter', filter);

        this._publishCountSelectedFilters();
        this._publishCountChangedFilters();
    },

    _onFilterShowCriteria: function(shownFilter) {
        if (this.autoClose) {
            _.each(this.filters, function(filter) {
                if (filter !== shownFilter) {
                    _.result(filter, 'ensurePopupCriteriaClosed');
                }
            });
        }

        this._publishCountSelectedFilters();
    },

    /**
     * Returns list of filter raw values
     */
    getValues: function() {
        const values = {};
        _.each(this.filters, function(filter) {
            if (filter.renderable) {
                values[filter.name] = filter.getValue();
            }
        }, this);

        return values;
    },

    /**
     * Sets raw values for filters
     */
    setValues: function(values) {
        _.each(values, function(value, name) {
            if (_.has(this.filters, name)) {
                this.filters[name].setValue(value);
            }
        }, this);
    },

    /**
     * Triggers when filter select is changed
     *
     * @param {Array} filters
     * @protected
     */
    _onChangeFilterSelect: function(filters) {
        this.trigger('updateList', this);
        this._processFilterStatus(filters);
        this.trigger('afterUpdateList', this);
    },

    /**
     * Enable filter
     *
     * @param {oro.filter.AbstractFilter} filter
     * @return {*}
     */
    enableFilter: function(filter) {
        return this.enableFilters([filter]);
    },

    /**
     * Disable filter
     *
     * @param {oro.filter.AbstractFilter} filter
     * @return {*}
     */
    disableFilter: function(filter) {
        return this.disableFilters([filter]);
    },

    /**
     * Enable filters
     *
     * @param {Array} filters []
     * @return {*}
     */
    enableFilters: function(filters = []) {
        if (!filters.length && !Array.isArray(filters)) {
            return this;
        }

        filters.forEach(filter => {
            this._renderFilter(filter);
            if (filter.visible) {
                filter.enable();
            }
        });

        return this;
    },

    /**
     * Disable filters
     *
     * @param {Array} filters []
     * @return {*}
     */
    disableFilters: function(filters = []) {
        if (!filters.length || !Array.isArray(filters)) {
            return this;
        }

        filters.forEach(filter => filter.disable());

        return this;
    },

    /**
     * @param {oro.filter.AbstractFilter} filter
     * @returns {oro.filter.AbstractFilter}
     */
    _renderFilter: function(filter) {
        if (!filter.isRendered()) {
            const oldEl = filter.$el;

            filter.setRenderMode(this.renderMode);
            // filter rendering process replaces $el
            filter.render();
            // so we need to replace element which keeps place in DOM with actual filter $el after rendering
            oldEl.replaceWith(filter.$el);
            filter.rendered();

            if (!filter.visible) {
                filter.hide();
            }
        }

        return filter;
    },

    getTemplateData: function() {
        return {
            filters: this.filters,
            renderMode: this.renderMode,
            outerHintContainer: this.outerHintContainer
        };
    },

    /**
     * Render filter list
     *
     * @return {*}
     */
    render: function() {
        FiltersManager.__super__.render.call(this);

        this.dropdownContainer = this.$el.find('.filter-container');
        const $filterItems = this.dropdownContainer.find('.filter-items');

        _.each(this.filters, function(filter) {
            if (_.isFunction(filter.setDropdownContainer)) {
                filter.setDropdownContainer(this.dropdownContainer);
            }

            filter.setRenderMode(this.renderMode);

            if (!filter.renderable || !filter.visible) {
                // append element to reserve space
                // empty elements are hidden by default
                $filterItems.append(filter.$el);
                return;
            }

            if (typeof filter.prerender === 'function') {
                filter.prerender();
            }

            filter.render();
            $filterItems.append(filter.$el);
            filter.rendered();
        }, this);

        this.trigger('rendered');

        if (_.isEmpty(this.filters)) {
            this.hide();
        }

        const filtersStateView = this.subview('filters-state');
        if (filtersStateView) {
            filtersStateView.render();
            if (this.viewMode === FiltersManager.MANAGE_VIEW_MODE) {
                filtersStateView.hide();
            }
        }

        if (this.viewMode === FiltersManager.STATE_VIEW_MODE) {
            this.hide();
        }

        this.appendToContainer();
        return this;
    },

    show: function() {
        this.$el.show();
        this.trigger('visibility-change', true);
    },

    hide: function() {
        this.$el.hide();
        this.trigger('visibility-change', false);
    },

    appendToContainer() {
        this.$el.prependTo(this.filterContainer);
        this.trigger('visibility-change', this.$el.is(':visible'));
    },

    /**
     * @param {Number} [count]
     * @private
     */
    _publishCountSelectedFilters: function(count) {
        const countFilters = (!_.isUndefined(count) && _.isNumber(count))
            ? count : this._calculateSelectedFilters();

        mediator.trigger(
            'filterManager:selectedFilters:count:' + this.collection.options.gridName,
            countFilters
        );

        this.$('a[data-name="filters-dropdown"]').toggleClass('filters-exist', countFilters > 0);
    },

    /**
     * @param {Number} [count]
     * @private
     */
    _publishCountChangedFilters: function(count) {
        const countFilters = (!_.isUndefined(count) && _.isNumber(count)) ? count : this._calculateChangedFilters();

        mediator.trigger(
            'filterManager:changedFilters:count:' + this.collection.options.gridName,
            countFilters
        );
    },

    /**
     * @returns {Number} count of selected filters
     * @private
     */
    _calculateSelectedFilters: function() {
        return _.reduce(this.filters, function(memo, filter) {
            const num = (
                filter.renderable &&
                !filter.isEmptyValue() &&
                !_.isEqual(filter.emptyValue, filter.value)
            ) ? 1 : 0;

            return memo + num;
        }, 0);
    },

    /**
     * @returns {Number} count of changed filters
     * @private
     */
    _calculateChangedFilters: function() {
        return this.getChangedFilters().length;
    },

    /**
     * @returns {jQuery.Element}
     */
    getHintContainer: function() {
        let $container = this.dropdownContainer;

        if (this.outerHintContainer) {
            $container = $(this.outerHintContainer);
        }

        return $container.find('.filter-items-hint');
    },

    _resetHintContainer: function() {
        const $container = this.getHintContainer();
        let show = false;
        $container.children('span').each(function() {
            if (this.style.display !== 'none') {
                show = true;
                return false;
            }
        });
        if (show) {
            $container.show();
        } else {
            $container.hide();
        }

        this._publishCountSelectedFilters();
    },

    /**
     * Set design for filter manager button
     *
     * @protected
     */
    _setButtonDesign: function($button) {
        $button.addClass('dropdown-toggle');
    },

    /**
     * Activate/deactivate all filter depends on its status
     *
     * @param {Array} activeFilters
     * @protected
     */
    _processFilterStatus: function(activeFilters) {
        if (!Array.isArray(activeFilters)) {
            activeFilters = this.$(this.filterSelector).val();
        }

        _.each(this.filters, function(filter, name) {
            if (!filter.renderable && _.indexOf(activeFilters, name) !== -1) {
                this.enableFilter(filter);
            } else if (filter.renderable && _.indexOf(activeFilters, name) === -1) {
                this.disableFilter(filter);
            }
        }, this);
    },

    /**
     * Reset filters value button click handler
     * @param {jQuery.Event} e
     */
    _onReset: function(e) {
        e.stopPropagation();
        this.collection.state.filters = {};
        this.collection.trigger('updateState', this.collection);
        mediator.trigger('datagrid:doRefresh:' + this.collection.inputName, true);
    },

    /**
     * Dropdown button toggle handler
     * @param e
     * @private
     */
    _onDropdownToggle: function(e) {
        e.preventDefault();
        const dialogWidget = new FilterDialogWidget({
            title: this.filterDialogTitle,
            content: this.dropdownContainer
        });

        dialogWidget.render();
    },

    /**
     * Handles click on body element
     * closes the filters-dropdown if event target does not belong to the view element
     *
     * @param {jQuery.Event} e
     * @protected
     */
    _onOutsideActionEvent: function(e) {
        if (!_.contains($(e.target).parents(), this.el)) {
            this.closeDropdown();
        }
    },

    /**
     * Close dropdown
     */
    closeDropdown: function() {
        this.$('.dropdown').removeClass('oro-open');
    },

    /**
     * On mobile closes filter box if value is changed
     */
    _onUpdateCriteriaClick: function(filter) {
        filter.once('update', this.closeDropdown, this);
        _.defer(filter.off.bind(filter, 'update', this.closeDropdown, this));
    },

    getViewMode: function() {
        return this.viewMode;
    },

    setViewMode: function(mode) {
        const modes = [FiltersManager.STATE_VIEW_MODE, FiltersManager.MANAGE_VIEW_MODE];

        if (this.viewMode === mode || !_.contains(modes, mode)) {
            return;
        }

        this.trigger('changeViewMode', mode);
        _.result(this.subview('filters-state'), mode === FiltersManager.STATE_VIEW_MODE ? 'show' : 'hide');
        this.viewMode = mode;
        persistentStorage.setItem(this.storageKey, mode);
    },

    getChangedFilters: function() {
        return _.filter(this.filters, function(filter) {
            return (
                filter.renderable &&
                filter._isDOMValueChanged()
            );
        });
    }
});

_.extend(FiltersManager, {
    MANAGE_VIEW_MODE: 'expanded',
    STATE_VIEW_MODE: 'collapsed'
});

export default FiltersManager;
