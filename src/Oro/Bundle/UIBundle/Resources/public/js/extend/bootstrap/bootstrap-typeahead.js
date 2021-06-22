define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');

    require('bootstrap-typeahead');

    /**
     * This customization allows to define own functions for Typeahead
     */
    const origTypeahead = $.fn.typeahead.Constructor;
    const origFnTypeahead = $.fn.typeahead;

    const typeaheadPatches = {
        _calculateRightPosition: function() {
            if (!this.$element.length) {
                return 0;
            }

            let $relativeParent = this.$element.parents().filter((index, el) => {
                return $(el).css('position') !== 'static';
            });

            if ($relativeParent.length === 0) {
                $relativeParent = $('body');
            }

            const relativeParentRight = $relativeParent.offset().left + $relativeParent.outerWidth();
            const elementRight = this.$element.offset().left + this.$element.outerWidth();
            const right = relativeParentRight - elementRight;

            return right < 0 ? 0 : right;
        },

        show: function() {
            // fix for dropdown menu position that placed inside scrollable containers
            const pos = $.extend({}, this.$element.position(), {
                height: this.$element[0].offsetHeight
            });

            const direction = {};

            if (_.isRTL()) {
                direction.right = this._calculateRightPosition();
            } else {
                direction.left = pos.left;
            }

            this.$menu
                .insertAfter(this.$element)
                .css({
                    top: pos.top + pos.height + this.scrollOffset(this.$element),
                    ...direction
                })
                .show();

            this.shown = true;
            return this;
        },
        scrollOffset: function($el) {
            // calculates additional offset of all scrolled on parents, except body and html
            let offset = 0;
            let stopProcess = false;

            $el.parents().each(function(i, el) {
                if (el !== document.body && el !== document.html && !stopProcess) {
                    offset += el.scrollTop;
                    stopProcess = $(el).css('position') === 'relative';
                }
            });

            return offset;
        },
        keypress: function() {
            // do nothing;
        },
        highlighter: function(item) {
            if (!this.query) {
                return item;
            }

            const root = document.createElement('div');
            root.innerHTML = item;

            const nested = element => {
                element.childNodes.forEach(node => {
                    if (node.nodeType === 3) {
                        const query = this.query.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g, '\\$&');
                        node.nodeValue = node.nodeValue.replace(
                            new RegExp('(' + query + ')', 'ig'),
                            '[strong]$&[/strong]'
                        );
                        return;
                    }
                    nested(node);
                });
            };
            nested(root);

            return root.innerHTML.replace(/\[strong\](.*?)\[\/strong\]/gi, '<strong>$1</strong>');
        }
    };

    const Typeahead = function(element, options) {
        const opts = $.extend({}, $.fn.typeahead.defaults, typeaheadPatches, options);

        _.each(opts, function(value, name) {
            this[name] = value || this[name];
        }, this);

        this.$holder = $(opts.holder || '');

        origTypeahead.call(this, element, options);
        this.$element
            .add(this.$holder)
            .add(this.$menu)
            .addClass(`typeahead-direction-${_.isRTL() ? 'end' : 'start'}`);
    };

    Typeahead.prototype = origTypeahead.prototype;
    Typeahead.prototype.constructor = Typeahead;

    $.fn.typeahead = function(option) {
        return this.each(function() {
            const $this = $(this);
            let data = $this.data('typeahead');
            const options = typeof option === 'object' && option;
            if (!data) {
                $this.data('typeahead', (data = new Typeahead(this, options)));
            }
            if (typeof option === 'string') {
                data[option]();
            }
        });
    };

    $.fn.typeahead.defaults = origFnTypeahead.defaults;
    $.fn.typeahead.Constructor = Typeahead;
    $.fn.typeahead.noConflict = origFnTypeahead.noConflict;
});
