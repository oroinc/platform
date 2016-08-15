define([
    'jquery',
    'jquery-ui',
    'jquery.multiselect.filter'
], function($) {
    'use strict';

    /**
     * Fixed issue with event's namespaces on document element, caused memory leak
     */

    $.widget('ech.multiselectfilter', $.ech.multiselectfilter, {
        _create: function() {
            var opts = this.options;
            var elem = $(this.element);

            // get the multiselect instance
            var instance = (this.instance =
                (elem.data('echMultiselect') || elem.data('multiselect') || elem.data('ech-multiselect')));

            // store header; add filter class so the close/check all/uncheck all links can be positioned correctly
            this.header = instance.menu.find('.ui-multiselect-header').addClass('ui-multiselect-hasfilter');

            // wrapper elem
            var wrapper = (this.wrapper =
                $('<div class="ui-multiselect-filter">' + (opts.label.length ? opts.label : '') +
                    '<input placeholder="' + opts.placeholder + '" type="search"' +
                    (/\d/.test(opts.width) ? 'style="width:' + opts.width + 'px"' : '') +
                    ' /></div>').prependTo(this.header));

            // reference to the actual inputs
            this.inputs = instance.menu.find('input[type="checkbox"], input[type="radio"]');

            // build the input box
            this.input = wrapper.find('input').bind({
                keydown: function(e) {
                    // prevent the enter key from submitting the form / closing the widget
                    if (e.which === 13) {
                        e.preventDefault();
                    }
                },
                keyup: $.proxy(this._handler, this),
                click: $.proxy(this._handler, this)
            });

            // cache input values for searching
            this.updateCache();

            // rewrite internal _toggleChecked fn so that when checkAll/uncheckAll is fired,
            // only the currently filtered elements are checked
            instance._toggleChecked = function(flag, group) {
                var $inputs = (group && group.length) ?  group : this.labels.find('input');
                var _self = this;

                // do not include hidden elems if the menu isn't open.
                var selector = instance._isOpen ?  ':disabled, :hidden' : ':disabled';

                $inputs = $inputs
                    .not(selector)
                    .each(this._toggleState('checked', flag));

                // update text
                this.update();

                // gather an array of the values that actually changed
                var values = $inputs.map(function() {
                    return this.value;
                }).get();

                // select option tags
                this.element.find('option').filter(function() {
                    if (!this.disabled && $.inArray(this.value, values) > -1) {
                        _self._toggleState('selected', flag).call(this);
                    }
                });

                // trigger the change event on the select
                if ($inputs.length) {
                    this.element.trigger('change');
                }
            };

            // rebuild cache when multiselect is updated
            var doc = $(document).bind('multiselectrefresh' + this.eventNamespace, $.proxy(function() {
                this.updateCache();
                this._handler();
            }, this));

            // automatically reset the widget on close?
            if (this.options.autoReset) {
                doc.bind('multiselectclose' + this.eventNamespace, $.proxy(this._reset, this));
            }
        },

        _destroy: function() {
            $(document).off(this.eventNamespace);
            this._super();
        }
    });
});
