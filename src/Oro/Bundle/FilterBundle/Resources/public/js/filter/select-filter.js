define(function(require, exports, module) {
    'use strict';

    const template = require('tpl-loader!orofilter/templates/filter/select-filter.html');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const TextFilter = require('oro/filter/text-filter');
    const {Multiselect, MultiselectDropdown} = require('oroui/js/app/views/multiselect');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const manageFocus = require('oroui/js/tools/manage-focus').default;
    const tools = require('oroui/js/tools');
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
    const SelectFilter = TextFilter.extend({
        optionNames: TextFilter.prototype.optionNames.concat(['View']),

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
         * Selector for close button
         *
         * @property
         */
        disableSelector: '.disable-filter',

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

        View: Multiselect,

        /**
         * Select widget options
         *
         * @property
         */
        widgetOptions: {
            multiple: false,
            cssConfig: {
                strategy: 'override',
                searchResetBtn: 'btn btn-icon btn-light clear-search'
            }
        },

        /**
         * Selector, jQuery object or HTML element that will be target for append multiselect dropdown menu
         *
         * @property
         */
        dropdownContainer: null,

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

        /**
         * Filter events
         *
         * @property
         */
        events: {
            'change select': '_onSelectChange'
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

            if (this.lazy && this.renderableByDefault) {
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
            SelectFilter.__super__.dispose.call(this);
        },

        getCriteriaValueFieldToFocus() {
            return this.$(manageFocus.getFirstTabbable(this.subview('multiselect').$(':tabbable').toArray()));
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
                selected: this.getSelectedValue(),
                isEmpty: this.isEmpty(),
                renderMode: this.renderMode,
                criteriaClass: this.getCriteriaExtraClass(),
                ...this.getTemplateDataProps()
            };
        },

        getSelectedValue() {
            return _.extend({}, this.emptyValue, this.value);
        },

        getWidgetConstructor() {
            if (this.templateTheme === 'embedded') {
                return MultiselectDropdown;
            }

            return this.View;
        },

        _onClickCriteriaSelector(event) {
            if (this.templateTheme === 'embedded') {
                return;
            }

            SelectFilter.__super__._onClickCriteriaSelector.call(this, event);
        },

        /**
         * Render filter view
         *
         * @return {*}
         */
        render: function() {
            if (this.isRendered() && this.subview('multiselect')) {
                const $content = $(this.template(this.getTemplateData()));
                const selectOptions = $content.find('select').html();

                this.$('select').html(selectOptions);

                this.subview('multiselect').refresh();
            } else {
                const {selectOptionsListAriaLabel} = this.getTemplateDataProps();

                this.resetFlags();
                const $filter = $(this.template(this.getTemplateData()));

                const View = this.getWidgetConstructor();

                this.subview('multiselect', new View({
                    autoRender: true,
                    container: $filter.find(this.inputSelector).parent(),
                    selectElement: $filter.find(this.inputSelector),
                    listAriaLabel: selectOptionsListAriaLabel,
                    enabledHeader: false,
                    enabledSearch: this.contextSearch,
                    closeAfterChose: this.closeAfterChose,
                    ...this.widgetOptions
                }));

                this._wrap($filter);
            }

            if (!this.loadedMetadata && !this.subview('loading')) {
                this.subview('loading', new LoadingMaskView({
                    container: this.$el
                }));
                this.subview('loading').show();
            }

            if (this.initiallyOpened) {
                this._showCriteria();
            }

            return this;
        },

        /**
         * @inheritdoc
        //  */
        setValue: function(value) {
            // When the system applies pre-defined view, it won't change type of filter value.
            // Select filters can have numeric values,
            // but internally all logic expects that all values will have string type.
            if (_.isNumber(value.value) && !_.isNaN(value.value)) {
                value.value = value.value.toString();
            }

            if (tools.isEqualsLoosely(this.value, value)) {
                return this;
            }

            return SelectFilter.__super__.setValue.call(this, value);
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
         * Triggers change data event
         *
         * @protected
         */
        _onSelectChange: function() {
            this._onValueChanged();
            this.applyValue();

            if (this.closeAfterChose) {
                this._hideCriteria();
            }
        },

        _showCriteria() {
            if (this.subview('multiselect') instanceof MultiselectDropdown) {
                this.subview('multiselect').show();
            }

            SelectFilter.__super__._showCriteria.call(this);
        },

        _hideCriteria() {
            if (this.subview('multiselect') instanceof MultiselectDropdown) {
                this.subview('multiselect').hide();
            }

            this.subview('multiselect').resetSearch();

            SelectFilter.__super__._hideCriteria.call(this);
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

            if (this.subview('multiselect') && !tools.isEqualsLoosely(newValue, oldValue)) {
                this.subview('multiselect').refresh();
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

        getTemplateDataProps() {
            const data = SelectFilter.__super__.getTemplateDataProps.call(this);

            return {
                ...data,
                selectOptionsListAriaLabel: __('oro.filter.select.options_list.aria_label', {
                    label: this.label
                })
            };
        },

        _isValid() {
            return true;
        }
    });

    return SelectFilter;
});
