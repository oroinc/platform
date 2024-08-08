define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const KEY_CODES = require('oroui/js/tools/keyboard-key-codes').default;
    require('jquery-ui/widget');
    require('jquery.multiselect.filter');

    /**
     * Fixed issue with event's namespaces on document element, caused memory leak
     */
    $.widget('ech.multiselectfilter', $.ech.multiselectfilter, {
        _superCreate: function() {
            const opts = this.options;
            const elem = $(this.element);

            // get the multiselect instance
            this.instance = elem.multiselect('instance');

            // store header; add filter class so the close/check all/uncheck all links can be positioned correctly
            this.header = this.instance.menu.find('.ui-multiselect-header').addClass('ui-multiselect-hasfilter');

            // wrapper elem
            this.input = $('<input/>').attr({
                placeholder: opts.placeholder,
                type: 'search'
            }).css({
                width: (/\d/.test(opts.width) ? `${opts.width}px` : null)
            }).on({
                keydown: function(e) {
                    // prevent the enter key from submitting the form / closing the widget
                    if (e.which === 13) {
                        e.preventDefault();
                    } else if (e.which === 27) {
                        elem.multiselect('close');
                        e.preventDefault();
                    } else if (e.which === 9 && e.shiftKey) {
                        elem.multiselect('close');
                        e.preventDefault();
                    } else if (e.altKey) {
                        switch (e.which) {
                            case 82:
                                e.preventDefault();
                                $(this).val('').trigger('input', '');
                                break;
                            case 65:
                                elem.multiselect('checkAll');
                                break;
                            case 85:
                                elem.multiselect('uncheckAll');
                                break;
                            case 76:
                                elem.multiselect('instance').labels.first().trigger('mouseenter');
                                break;
                        }
                    }
                },
                input: _.debounce(this._handler, opts.debounceMS).bind(this),
                search: this._handler.bind(this)
            });
            // automatically reset the widget on close?
            if (this.options.autoReset) {
                elem.on('multiselectclose', this._reset.bind(this));
            }
            // rebuild cache when multiselect is updated
            elem.on('multiselectrefresh', () => {
                this.updateCache();
                this._handler();
            });
            this.wrapper = $('<div/>')
                .addClass('ui-multiselect-filter')
                .text(opts.label)
                .append(this.input)
                .prependTo(this.header);

            // reference to the actual inputs
            this.inputs = this.instance.menu.find('input[type="checkbox"], input[type="radio"]');

            // cache input values for searching
            this.updateCache();

            // rewrite internal _toggleChecked fn so that when checkAll/uncheckAll is fired,
            // only the currently filtered elements are checked
            this.instance._toggleChecked = function(flag, group) {
                let $inputs = (group && group.length) ? group : this.labels.find('input');
                const _self = this;

                // do not include hidden elems if the menu isn't open.
                const selector = _self._isOpen ? ':disabled, :hidden' : ':disabled';

                $inputs = $inputs
                    .not(selector)
                    .each(this._toggleState('checked', flag));

                // update text
                this.update();

                // gather an array of the values that actually changed
                const values = {};
                $inputs.each(function() {
                    values[this.value] = true;
                });

                // select option tags
                this.element.find('option').filter(function() {
                    if (!this.disabled && values[this.value]) {
                        _self._toggleState('selected', flag).call(this);
                    }
                });

                // trigger the change event on the select
                if ($inputs.length) {
                    this.element.trigger('change');
                }
            };
        },

        _create: function(...args) {
            const {searchAriaLabel} = this.options;
            this._superCreate(args);

            this.input.attr('aria-label', searchAriaLabel ? searchAriaLabel : null);

            const elem = $(this.element);
            // Remove original an event handler and attach new one based on original
            this.input.off('keydown').on(`keydown${this._namespaceID}`, e => {
                if (e.which === KEY_CODES.ENTER) {
                    e.preventDefault();
                } else if (e.altKey) {
                    switch (e.which) {
                        case KEY_CODES.R:
                            e.preventDefault();
                            $(this).val('').trigger('input', '');
                            break;
                        case KEY_CODES.A:
                            elem.multiselect('checkAll');
                            break;
                        case KEY_CODES.U:
                            elem.multiselect('uncheckAll');
                            break;
                        case KEY_CODES.L:
                            elem.multiselect('instance').labels.first().trigger('mouseenter');
                            break;
                    }
                }
            });
        },

        // Modified original _handler to avoid deprecated jQuery methods
        _superHandler: function(e) {
            const rEscape = /[\-\[\]{}()*+?.,\\\^$|#\s]/g;
            const term = this.input[0].value.toLowerCase().trim();

            // speed up lookups
            const rows = this.rows;
            const inputs = this.inputs;
            const cache = this.cache;
            const $groups = this.instance.menu.find('.ui-multiselect-optgroup');
            $groups.show();
            if (!term) {
                rows.show();
            } else {
                rows.hide();

                const regex = new RegExp(term.replace(rEscape, '\\$&'), 'gi');

                this._trigger('filter', e, $.map(cache, function(v, i) {
                    if (v.search(regex) !== -1) {
                        rows.eq(i).show();
                        return inputs.get(i);
                    }

                    return null;
                }));
            }

            // show/hide optgroups
            $groups.each(function() {
                const $this = $(this);
                if (!$this.children('li:visible').length) {
                    $this.hide();
                }
            });
            this.instance._setMenuHeight();
        },

        _handler(e) {
            if (this.cache) {
                this._superHandler(e);
                this.instance.position();
            }
        },

        updateCache() {
            if (this.instance.labels) {
                this._super();
            }
        },

        _destroy() {
            $(this.element).off();
            this.input.off(`${this._namespaceID}`);
            return this._super();
        }
    });
});
