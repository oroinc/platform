define(function(require, exports, module) {
    'use strict';

    const template = require('tpl-loader!orofilter/templates/filter/select-filter.html');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const AbstractFilter = require('oro/filter/abstract-filter');
    const MultiselectDecorator = require('orofilter/js/multiselect-decorator');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    let config = require('module-config').default(module.id);

    config = _.extend({
        populateDefault: true
    }, config);

    /**
     * Select filter: filter value as select option
     *
     * @export  oro/filter/select-filter
     * @class   oro.filter.SelectFilter
     * @extends oro.filter.AbstractFilter
     */
    const SelectFilter = AbstractFilter.extend({
        /**
         * @property
         */

        MultiselectDecorator: MultiselectDecorator,

        /**
         * Filter selector template
         *
         * @property
         */
        template: template,
        templateSelector: '#select-filter-template',

        /**
         * Should default value be added to options list
         *
         * @property
         */
        populateDefault: config.populateDefault,

        /**
         * Selector for filter area
         *
         * @property
         */
        containerSelector: '.filter-select',

        /**
         * Selector for close button
         *
         * @property
         */
        disableSelector: '.disable-filter',

        /**
         * Selector for widget button
         *
         * @property
         */
        buttonSelector: '.filter-criteria-selector',

        /**
         * Selector to criteria popup container
         *
         * @property {String}
         */
        criteriaSelector: '.filter-criteria',

        /**
         * Selector for select input element
         *
         * @property
         */
        inputSelector: 'select',

        /**
         * Select widget object
         *
         * @property
         */
        selectWidget: null,

        /**
         * Minimum widget menu width, calculated depends on filter options
         *
         * @property
         */
        cachedMinimumWidth: null,

        /**
         * Select widget options
         *
         * @property
         */
        widgetOptions: {
            multiple: false,
            classes: 'select-filter-widget'
        },

        /**
         * Selector, jQuery object or HTML element that will be target for append multiselect dropdown menu
         *
         * @property
         */
        dropdownContainer: null,

        /**
         * Select widget menu opened flag
         *
         * @property
         */
        selectDropdownOpened: false,

        /**
         * @property {Boolean}
         */
        contextSearch: true,

        /**
         * @property {Boolean}
         */
        closeAfterChose: true,

        /**
         * @property {Boolean}
         */
        loadedMetadata: true,

        noWrap: true,

        /**
         * Filter events
         *
         * @property
         */
        events: {
            'keydown select': '_preventEnterProcessing',
            'keydown .filter-criteria-selector': '_triggerEventOnCriteriaToggle',
            'focusin .filter-criteria-selector': '_triggerEventOnCriteriaToggle',
            'focusout .filter-criteria-selector': '_triggerEventOnCriteriaToggle',
            'click .filter-select': '_onClickFilterArea',
            'click .disable-filter': '_onClickDisableFilter',
            'change select': '_onSelectChange',
            'multiselectbeforeclose': function() {
                return this.autoClose !== false;
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function SelectFilter(options) {
            SelectFilter.__super__.constructor.call(this, options);
        },

        /**
         * Initialize.
         *
         * @param {Object} options
         */
        initialize: function(options) {
            const opts = _.pick(options, 'choices', 'dropdownContainer', 'widgetOptions');
            $.extend(true, this, opts);

            this._setChoices(this.choices);

            // init empty value object if it was not initialized so far
            if (_.isUndefined(this.emptyValue)) {
                this.emptyValue = {
                    value: ''
                };
            }

            SelectFilter.__super__.initialize.call(this, options);

            if (this.lazy) {
                this.loadedMetadata = false;
                this.loader(
                    metadata => {
                        this._setChoices(metadata.choices);
                        this.render();
                        if (this.subview('loading')) {
                            this.subview('loading').hide();
                        }
                    }
                );
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.choices;
            this._disposeSelectWidget();
            SelectFilter.__super__.dispose.call(this);
        },

        /**
         * @inheritdoc
         */
        getTemplateData: function() {
            const options = this.choices.slice(0);
            if (this.populateDefault) {
                options.unshift({value: '', label: this.placeholder || this.populateDefault});
            }

            return {
                label: this.labelPrefix + this.label,
                showLabel: this.showLabel,
                options: options,
                selected: _.extend({}, this.emptyValue, this.value),
                isEmpty: this.isEmpty(),
                renderMode: this.renderMode,
                criteriaClass: this.getCriteriaExtraClass(),
                ...this.getTemplateDataProps()
            };
        },

        resetFlags() {
            this.selectDropdownOpened = false;
        },

        /**
         * Render filter template
         *
         * @return {*}
         */
        render: function() {
            let toShowCriteria = false;

            // Just update filter options
            if (this.isRendered() && this.selectWidget) {
                toShowCriteria = this.selectWidget.multiselect('isOpen');

                const $content = $(this.template(this.getTemplateData()));
                const selectOptions = $content.find('select').html();

                this.$('select').html(selectOptions);
                this.selectWidget.multiselect('refresh');
            } else {
                this.resetFlags();
                SelectFilter.__super__.render.call(this);
                this._initializeSelectWidget();
            }

            if (!this.loadedMetadata && !this.subview('loading')) {
                this.subview('loading', new LoadingMaskView({
                    container: this.$el
                }));
                this.subview('loading').show();
            }

            if (this.initiallyOpened || toShowCriteria) {
                this._showCriteria();
            }

            // Hide a filer after re-rendering if it was hidden before
            if (!this.visible) {
                this.hide();
            }

            return this;
        },

        /**
         * Set dropdownContainer for dropdown element
         *
         * @param {(jQuery|Element|String)} container
         * @protected
         */
        setDropdownContainer: function(container) {
            this.dropdownContainer = $(container);
        },

        /**
         * @inheritdoc
         */
        hide: function() {
            // when the filter has been opened and becomes invisible - close multiselect too
            if (this.selectWidget) {
                this.selectWidget.multiselect('close');
            }

            return SelectFilter.__super__.hide.call(this);
        },

        /**
         * @inheritdoc
         */
        setValue: function(value) {
            // When the system applies pre-defined view, it won't change type of filter value.
            // Select filters can have numeric values,
            // but internally all logic expects that all values will have string type.
            if (_.isNumber(value.value) && !_.isNaN(value.value)) {
                value.value = value.value.toString();
            }

            return SelectFilter.__super__.setValue.call(this, value);
        },

        _disposeSelectWidget() {
            if (this.selectWidget) {
                this.$(this.inputSelector).off(`remove${this.eventNamespace()}`);
                this.selectWidget.dispose();
                delete this.selectWidget;
            }
        },

        /**
         * Initialize multiselect widget
         *
         * @protected
         */
        _initializeSelectWidget: function() {
            this._disposeSelectWidget();

            const position = this._getSelectWidgetPosition();
            const {selectOptionsListAriaLabel} = this.getTemplateDataProps();

            this.selectDropdownOpened = false;
            this.$(this.inputSelector).on(`remove${this.eventNamespace()}`, this._disposeSelectWidget.bind(this));
            this.selectWidget = new this.MultiselectDecorator({
                element: this.$(this.inputSelector),
                parameters: _.extend({
                    noneSelectedText: this.placeholder,
                    showCheckAll: false,
                    showUncheckAll: false,
                    outerTrigger: this.$(this.buttonSelector),
                    selectedText: (numChecked, numTotal, checkedItems) => {
                        return this._getSelectedText(checkedItems);
                    },
                    position: position,
                    beforeopen: () => {
                        this.selectWidget.onBeforeOpenDropdown();
                    },
                    open: () => {
                        this.selectWidget.onOpenDropdown();
                        this._setDropdownWidth();
                        this._setButtonPressed(this.$(this.containerSelector), true);
                        this.trigger('showCriteria', this);
                        this._clearChoicesStyle();
                        this.selectDropdownOpened = true;

                        this.selectWidget.updateDropdownPosition($.extend({}, position, {
                            within: this._findDropdownFitContainer(this.dropdownContainer) || this.dropdownContainer
                        }));
                    },
                    refresh: () => {
                        this.selectWidget.onRefresh();
                    },
                    beforeclose: () => {
                        return this.closeAfterChose;
                    },
                    close: () => {
                        this._setButtonPressed(this.$(this.containerSelector), false);
                        this.trigger('hideCriteria', this);
                        if (!this.disposed) {
                            this.selectDropdownOpened = false;
                        }
                    },
                    appendTo: this._appendToContainer(),
                    refreshNotOpened: this.templateTheme !== '',
                    listAriaLabel: selectOptionsListAriaLabel,
                    preventTabOutOfContainer: this.isDropdownRenderMode()
                }, this.widgetOptions),
                contextSearch: this.contextSearch,
                filterLabel: this.label
            });
            this.selectWidget.multiselect('getButton').addClass('select-widget-trigger');
            this.selectWidget.setViewDesign(this);
            this.selectWidget.getWidget().on('keyup', e => {
                if (e.keyCode === 27 && this.autoClose !== false) {
                    this._onClickFilterArea(e);
                }
            });
        },

        _showCriteria() {
            if (this.selectWidget) {
                this.selectWidget.multiselect('open');
            }
        },

        _hideCriteria() {
            if (this.selectWidget) {
                this.selectWidget.multiselect('close');
            }
        },

        /**
         * Get position to multiselect widget
         *
         * @returns {{my: string, at: string, of: *, collision: string, within: (*|null)}}
         * @private
         */
        _getSelectWidgetPosition: function() {
            return {
                my: `${_.isRTL() ? 'right' : 'left'} top+8`,
                at: `${_.isRTL() ? 'right' : 'left'} bottom`,
                of: this.$el,
                collision: _.isMobile() ? 'none' : 'fit none',
                within: this._findDropdownFitContainer(this.dropdownContainer) || this.dropdownContainer
            };
        },

        /**
         * Append multiselect widget to container
         * @return {jQuery}
         */
        _appendToContainer: function() {
            return this.$el;
        },

        /**
         * Remove styles from choices list
         *
         * @protected
         */
        _clearChoicesStyle: function() {
            const labels = this.selectWidget.getWidget().find('label');
            labels.removeClass('ui-state-hover');
            if (_.isEmpty(this.value.value)) {
                labels.removeClass('ui-state-active');
            }
        },

        /**
         * Get text for filter hint
         *
         * @param {Array} checkedItems
         * @protected
         */
        _getSelectedText: function(checkedItems) {
            if (_.isEmpty(checkedItems)) {
                return this.placeholder;
            }

            const elements = [];
            _.each(checkedItems, function(element) {
                const title = element.getAttribute('title');
                if (title) {
                    elements.push(title);
                }
            });
            return elements.join(', ');
        },

        /**
         * Get criteria hint value
         *
         * @return {String}
         */
        _getCriteriaHint: function(...args) {
            const value = (args.length > 0) ? this._getDisplayValue(args[0]) : this._getDisplayValue();
            const choice = _.find(this.choices, function(c) {
                return (c.value === value.value);
            });
            return !_.isUndefined(choice) ? choice.label : this.placeholder;
        },

        /**
         * Set design for select dropdown
         *
         * @protected
         */
        _setDropdownWidth: function() {
            if (!this.cachedMinimumWidth) {
                this.cachedMinimumWidth = this.selectWidget.getMinimumDropdownWidth() + 24;
            }
            const widget = this.selectWidget.getWidget();
            const filterWidth = this.$(this.containerSelector).width();
            const requiredWidth = Math.max(filterWidth + 24, this.cachedMinimumWidth);
            widget.width(requiredWidth).css('min-width', requiredWidth + 'px');
        },

        _triggerEventOnCriteriaToggle(e) {
            this.trigger(`${e.type}OnToggle`, e, this);
        },

        focusCriteriaToggler() {
            this.getCriteriaSelector().trigger('focus');
        },

        /**
         * Open/close select dropdown
         *
         * @param {Event} e
         * @protected
         */
        _onClickFilterArea: function(e) {
            if (!this.selectDropdownOpened) {
                this.selectWidget.multiselect('open');
            } else {
                this.selectWidget.multiselect('close');
            }

            e.stopPropagation();
        },

        /**
         * Triggers change data event
         *
         * @protected
         */
        _onSelectChange: function() {
            this._onValueChanged();
            // set value
            this.applyValue();
            // update dropdown
            this.selectWidget.updateDropdownPosition();
        },

        /**
         * Handle click on filter disabler
         *
         * @param {Event} e
         */
        _onClickDisableFilter: function(e) {
            e.preventDefault();
            this.disable();
        },

        /**
         * @inheritdoc
         */
        _onValueUpdated: function(newValue, oldValue) {
            SelectFilter.__super__._onValueUpdated.call(this, newValue, oldValue);
            if (this.selectWidget) {
                this.selectWidget.multiselect('refresh');
            }
        },

        /**
         * @inheritdoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.inputSelector, value.value);
            return this;
        },

        /**
         * @inheritdoc
         */
        _readDOMValue: function() {
            return {
                value: this._getInputValue(this.inputSelector)
            };
        },

        _setChoices: function(choices) {
            choices = choices || [];

            // temp code to keep backward compatible
            this.choices = _.map(choices, function(option, i) {
                return _.isString(option) ? {value: i, label: option} : option;
            });
        },

        /**
         * @inheritdoc
         */
        _isDOMValueChanged: function() {
            const thisDOMValue = this._readDOMValue();
            return (
                !_.isUndefined(thisDOMValue.value) &&
                !_.isNull(thisDOMValue.value) &&
                !_.isEqual(this.value, thisDOMValue)
            );
        },

        getCriteriaSelector() {
            return this.$('.filter-criteria-selector');
        },

        getCriteria() {
            return this.$(this.criteriaSelector);
        },

        getTemplateDataProps() {
            const data = SelectFilter.__super__.getTemplateDataProps.call(this);

            return {
                ...data,
                selectOptionsListAriaLabel: __('oro.filter.select.options_list.aria_label', {
                    label: this.label
                })
            };
        }
    });

    return SelectFilter;
});
