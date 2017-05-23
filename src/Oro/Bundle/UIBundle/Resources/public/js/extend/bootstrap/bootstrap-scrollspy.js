define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    require('bootstrap');

    var _superScrollSpy = $.fn.scrollspy;

    var ScrollSpy = function(element, options) {
        _superScrollSpy.Constructor.apply(this, arguments);

        var self = this;
        var $element = $(element);
        var observer;

        if (MutationObserver) {
            observer = new MutationObserver(_.debounce(function(mutations) {
                // Destroy scrollspy if element is not exist in the DOM
                if ($(document).find($element).length) {
                    $element.scrollspy('refresh');
                } else {
                    self.destroy();
                }
            }, 50));

            this.observer = observer;
        }

        if (observer) {
            // scrollspy refresh on tag body leads to infinite toggling snizzle id
            var $collection = $element.is('body') ? $element.children() : $element;
            $collection.each(function() {
                observer.observe(this, {
                    attributes: true,
                    childList: true,
                    subtree: true,
                    characterData: true
                });
            });
        }
    };

    ScrollSpy.prototype = _.extend(Object.create(_superScrollSpy.Constructor.prototype), {

        constructor: ScrollSpy,

        /**
         * Method for destroy scrollspy, disable event listener
         * disconnect observer if that exist
         */
        destroy: function() {
            this.$scrollElement.off('scroll.scroll-spy.data-api');
            if (this.observer) {
                this.observer.disconnect();
            }
        }
    });

    $.fn.scrollspy = $.extend(function(option) {
        return this.each(function() {
            var $this = $(this);
            var data = $this.data('scrollspy');
            var options = typeof option === 'object' && option;
            if (!data) {
                $this.data('scrollspy', (data = new ScrollSpy(this, options)));
            }
            if (typeof option === 'string') {
                data[option]();
            }
        });
    }, _superScrollSpy);

    $.fn.scrollspy.Constructor = ScrollSpy;
});
