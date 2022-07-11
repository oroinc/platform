import $ from 'jquery';
import _ from 'underscore';
import layout from 'oroui/js/layout';
import mediator from 'oroui/js/mediator';
import manageFocus from 'oroui/js/tools/manage-focus';
import Popper from 'popper';
import DatePickerView from 'oroui/js/app/views/datepicker/datepicker-view';

import KEY_CODES from 'oroui/js/tools/keyboard-key-codes';

const FilterDatePickerView = DatePickerView.extend({
    /**
     * @inheritdoc
     */
    constructor: function FilterDatePickerView(options) {
        FilterDatePickerView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     *
     * @param {Object} options
     */
    initPickerWidget(options) {
        this.initDropdown(options);
        this.initDatePicker(options);
    },

    /**
     * @inheritdoc
     *
     * @param {Object} options
     */
    createFrontField(options) {
        FilterDatePickerView.__super__.createFrontField.call(this, options);
        this.$frontDateField.on('keydown', this.onKeyDownDropdownTrigger.bind(this));
    },

    /**
     * @param {Object} options
     */
    initDropdown(options) {
        this.$dropdown = this.$frontDateField
            .wrap('<div class="dropdown datefilter">').parent();
        this.$dropdown
            .append('<div class="dropdown-menu dropdown-menu-calendar"><div class="tab-content"></div></div>')
            .on('shown.bs.dropdown', this.onOpen.bind(this))
            .on(`keydown${this.eventNamespace()}`, this.closeOnEscape.bind(this));
        this.$frontDateField.attr({
            'data-toggle': 'dropdown',
            'data-placement': 'bottom-start',
            'data-display-arrow': false,
            'data-flip': false,
            'data-position-fixed': false
        }).data({
            onDestroy(instance) {
                if (instance.state.__prevElementsStyle) {
                    for (const [element, style] of instance.state.__prevElementsStyle) {
                        const entries = Object.entries(style)[0];

                        element.style[entries[0]] = entries[1];
                    }
                    delete instance.state.__prevElementsStyle;
                    mediator.trigger('layout:reposition');
                }
            },
            modifiers: {
                hide: {
                    enabled: false
                },
                offset: {
                    enabled: true,
                    fn(data, options) {
                        const scrollElement = data.instance.state.scrollElement;
                        const popperReact = data.instance.popper.getBoundingClientRect();
                        const bottom = Math.round(popperReact.bottom);
                        const rootEl = layout.getRootElement();
                        let scrollRootElement = scrollElement;

                        if (
                            document.body.isSameNode(scrollElement) &&
                            bottom > document.body.clientHeight
                        ) {
                            if (!data.instance.state.__prevElementsStyle) {
                                data.instance.state.__prevElementsStyle = [
                                    [document.body, {
                                        overflowY: document.body.style.overflowY
                                    }],
                                    [rootEl, {
                                        minHeight: rootEl.style.minHeight
                                    }]
                                ];
                            }

                            document.body.style.overflowY = 'scroll';
                            rootEl.style.minHeight = `${bottom}px`;
                            scrollRootElement = rootEl;
                            mediator.trigger('layout:reposition');
                        }

                        const shift = scrollRootElement.scrollWidth - scrollRootElement.clientWidth;

                        if (popperReact.right > scrollRootElement.clientWidth && shift > 0) {
                            options.offset = _.isRTL() ? `${shift}, 0` : `${-shift}, 0`;
                        }

                        Popper.Defaults.modifiers.offset.fn(data, options);
                        return data;
                    }
                }
            }});
    },

    /**
     * Initializes date picker widget
     *
     * @param {Object} options
     */
    initDatePicker: function(options) {
        const widgetOptions = {};
        this.$calendar = this.getCalendarElement();
        _.extend(widgetOptions, options.datePickerOptions, {
            onSelect: this.onSelect.bind(this)
        });
        this.$calendar.datepicker(widgetOptions);
        this.$calendar.addClass(widgetOptions.className)
            .click(function(e) {
                e.stopImmediatePropagation();
            });
        this.$calendar.on(`keydown${this.eventNamespace()}`, this.closeOnEscape.bind(this));
    },

    /**
     * Find HTML element witch will use as calendar main element
     *
     * @return {HTMLElement}
     */
    getCalendarElement() {
        return this.$dropdown.find('.dropdown-menu .tab-content');
    },

    /**
     * Opens dropdown with date-picker
     *
     * @param {Object} event
     */
    onOpen: function(event) {
        if (event.namespace !== 'bs.dropdown') {
            // handle only events triggered with proper NS (omit just any show events)
            return;
        }

        this.$calendar.datepicker('refresh');
        manageFocus.focusTabbable(this.$calendar, this.$calendar.find('.ui-datepicker-calendar'));
        this.trigger('open', this);
    },

    /**
     * Handles pick date event
     *
     * @param {string} date
     */
    onSelect(date) {
        this.$frontDateField.val(date);
        FilterDatePickerView.__super__.onSelect.call(this, date);
        this.close();
    },

    /**
     * Closes dropdown
     */
    close() {
        this.$dropdown.trigger('tohide');
        this.$frontDateField.focus();
        this.trigger('close', this);
    },

    /**
     * Close dropdown on press Escape key
     * @param {Object} event
     */
    closeOnEscape(event) {
        // Prevent close dropdown if calendar is open
        if (event.keyCode === KEY_CODES.ESCAPE && this.$calendar.is(':visible')) {
            event.stopPropagation();
            this.close();
        }
    },

    /**
     * Handle and navigate to dropdown when toggler in focus
     * @param event
     */
    onKeyDownDropdownTrigger(event) {
        const $target = $(event.target);

        switch (event.keyCode) {
            case KEY_CODES.ARROW_UP:
            case KEY_CODES.ARROW_DOWN:
                event.preventDefault();
                $target.dropdown('show');
                break;
            case KEY_CODES.ESCAPE:
                if (this.$calendar.is(':visible')) {
                    event.stopPropagation();
                    $target.dropdown('hide');
                }
                break;
        }
    },

    /**
     * Destroys picker widget
     */
    destroyPickerWidget() {
        if (this.disposed) {
            return;
        }

        this.$calendar.off(this.eventNamespace());
        this.$dropdown.off(this.eventNamespace());
        this.$calendar.datepicker('destroy');
        this.$frontDateField.unwrap();
        delete this.$calendar;
        delete this.$dropdown;
    }
});

export default FilterDatePickerView;
