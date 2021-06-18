define(function(require) {
    'use strict';

    const $ = require('jquery');
    const KEY_CODES = require('oroui/js/tools/keyboard-key-codes').default;
    require('jquery-ui/widget');
    require('jquery.multiselect.filter');

    /**
     * Fixed issue with event's namespaces on document element, caused memory leak
     */
    $.widget('ech.multiselectfilter', $.ech.multiselectfilter, {
        _create: function(...args) {
            const {searchAriaLabel} = this.options;
            const superResult = this._superApply(args);

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
            return superResult;
        },

        _handler(e) {
            if (this.cache) {
                this._super(e);
                this.instance.position();
            }
        },

        updateCache() {
            if (this.instance.labels) {
                this._super();
            }
        },

        _destroy() {
            $(this.element).unbind();
            this.input.off(`${this._namespaceID}`);
            return this._super();
        }
    });
});
