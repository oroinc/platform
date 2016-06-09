define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    './abstract-filter',
    'orofilter/js/multiselect-decorator',
    'oroui/js/app/views/loading-mask-view'
], function($, _, __, AbstractFilter, MultiselectDecorator, LoadingMaskView) {
    'use strict';

    var SelectFilter;

    /**
     * Select filter: filter value as select option
     *
     * @export  oro/filter/select-filter
     * @class   oro.filter.SelectFilter
     * @extends oro.filter.AbstractFilter
     */
    SelectFilter = AbstractFilter.extend({
        /**
         * Filter selector template
         *
         * @property
         */
        templateSelector: '#select-filter-template',

        /**
         * Should default value be added to options list
         *
         * @property
         */
        populateDefault: true,

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
        dropdownContainer: 'body',

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
        loadedMetadata: true,

        /**
         * Filter events
         *
         * @property
         */
        events: {
            'keydown select': '_preventEnterProcessing',
            'click .filter-select': '_onClickFilterArea',
            'click .disable-filter': '_onClickDisableFilter',
            'change select': '_onSelectChange'
        },

        /**
         * Initialize.
         *
         * @param {Object} options
         */
        initialize: function(options) {
            var opts = _.pick(options || {}, ['choices', 'dropdownContainer']);
            _.extend(this, opts);

            this._setChoices(this.choices);

            // init empty value object if it was not initialized so far
            if (_.isUndefined(this.emptyValue)) {
                this.emptyValue = {
                    value: ''
                };
            }

            SelectFilter.__super__.initialize.apply(this, arguments);

            if (this.lazy) {
                this.loadedMetadata = false;
                this.loader(
                    _.bind(function(metadata) {
                        this._setChoices(metadata.choices);
                        this.render();
                        if (this.subview('loading')) {
                            this.subview('loading').hide();
                        }
                    }, this)
                );
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.choices;
            if (this.selectWidget) {
                this.selectWidget.dispose();
                delete this.selectWidget;
            }
            SelectFilter.__super__.dispose.call(this);
        },

        /**
         * Render filter template
         *
         * @return {*}
         */
        render: function() {
            var options = this.choices.slice(0);
            if (this.populateDefault) {
                options.unshift({value: '', label: this.placeholder});
            }

            var html = this.template({
                label: this.labelPrefix + this.label,
                showLabel: this.showLabel,
                options: options,
                canDisable: this.canDisable,
                selected: _.extend({}, this.emptyValue, this.value),
                isEmpty: this.isEmpty()
            });

            if (!this.selectWidget) {
                this.setElement(html);
                this._initializeSelectWidget();
            } else {
                var selectOptions = $(html).find('select').html();
                this.$('select').html(selectOptions);
                this.selectWidget.multiselect('refresh');
            }

            if (!this.loadedMetadata && !this.subview('loading')) {
                this.subview('loading', new LoadingMaskView({
                    container: this.$el
                }));
                this.subview('loading').show();
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
         * Initialize multiselect widget
         *
         * @protected
         */
        _initializeSelectWidget: function() {
            var $dropdownContainer = this._findDropdownFitContainer(this.dropdownContainer) || this.dropdownContainer;
            this.selectWidget = new MultiselectDecorator({
                element: this.$(this.inputSelector),
                parameters: _.extend({
                    noneSelectedText: this.placeholder,
                    selectedText: _.bind(function(numChecked, numTotal, checkedItems) {
                        return this._getSelectedText(checkedItems);
                    }, this),
                    position: {
                        my: 'left top+7',
                        at: 'left bottom',
                        of: this.$(this.containerSelector),
                        collision: 'fit none',
                        within: $dropdownContainer
                    },
                    open: _.bind(function() {
                        this.selectWidget.onOpenDropdown();
                        this._setDropdownWidth();
                        this._setButtonPressed(this.$(this.containerSelector), true);
                        this._clearChoicesStyle();
                        this.selectDropdownOpened = true;
                        this.selectWidget.updateDropdownPosition();
                    }, this),
                    close: _.bind(function() {
                        this._setButtonPressed(this.$(this.containerSelector), false);
                        setTimeout(_.bind(function() {
                            if (!this.disposed) {
                                this.selectDropdownOpened = false;
                            }
                        }, this), 100);
                    }, this),
                    appendTo: this.dropdownContainer
                }, this.widgetOptions),
                contextSearch: this.contextSearch
            });

            this.selectWidget.setViewDesign(this);
            this.selectWidget.getWidget().on('keyup', _.bind(function(e) {
                if (e.keyCode === 27) {
                    this._onClickFilterArea(e);
                }
            }, this));
        },

        /**
         * Remove styles from choices list
         *
         * @protected
         */
        _clearChoicesStyle: function() {
            var labels = this.selectWidget.getWidget().find('label');
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

            var elements = [];
            _.each(checkedItems, function(element) {
                var title = element.getAttribute('title');
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
        _getCriteriaHint: function() {
            var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();
            var choice = _.find(this.choices, function(c) {
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
            var widget = this.selectWidget.getWidget();
            var filterWidth = this.$(this.containerSelector).width();
            var requiredWidth = Math.max(filterWidth + 24, this.cachedMinimumWidth);
            widget.width(requiredWidth).css('min-width', requiredWidth + 'px');
            widget.find('input[type="search"]').width(requiredWidth - 30);
        },

        /**
         * Open/close select dropdown
         *
         * @param {Event} e
         * @protected
         */
        _onClickFilterArea: function(e) {
            if (!this.selectDropdownOpened) {
                setTimeout(_.bind(function() {
                    this.selectWidget.multiselect('open');
                }, this), 50);
            } else {
                setTimeout(_.bind(function() {
                    this.selectWidget.multiselect('close');
                }, this), 50);
            }

            e.stopPropagation();
        },

        /**
         * Triggers change data event
         *
         * @protected
         */
        _onSelectChange: function() {
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
         * @inheritDoc
         */
        _onValueUpdated: function(newValue, oldValue) {
            SelectFilter.__super__._onValueUpdated.apply(this, arguments);
            this.selectWidget.multiselect('refresh');
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.inputSelector, value.value);
            return this;
        },

        /**
         * @inheritDoc
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
        }
    });

    return SelectFilter;
});
