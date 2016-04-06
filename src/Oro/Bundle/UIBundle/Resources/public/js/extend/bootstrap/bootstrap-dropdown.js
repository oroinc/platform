define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    require('bootstrap');
    var toggleDropdown = '[data-toggle=dropdown]';

    function getParent($this) {
        var selector = $this.attr('data-target');
        var $parent;
        if (!selector) {
            selector = $this.attr('href');
            selector = selector && /#/.test(selector) && selector.replace(/.*(?=#[^\s]*$)/, ''); //strip for ie7
        }
        $parent = selector && $(selector);
        if (!$parent || !$parent.length) {
            $parent = $this.parent();
        }
        return $parent;
    }

    function beforeClearMenus() {
        $(toggleDropdown).each(function() {
            var $parent = getParent($(this));
            if ($parent.hasClass('open')) {
                $parent.trigger('hide.bs.dropdown');
            }
        });
    }

    /**
     * Override for Dropdown constructor
     *  - added destroy method, which removes event handlers from <html /> node
     *  - overloaded click handler on toggleDropdown element
     *    * executes custom clearMenu method
     *    * triggers 'shown.bs.dropdown' event on '.dropdown-menu' parent
     *
     * @param {HTMLElement} element
     * @constructor
     */
    function Dropdown(element) {
        var $el = $(element).on('click.dropdown.data-api', this.toggle);
        var globalHandlers = {
            'click.dropdown.data-api': function() {
                var $dropdown = $el.parent();
                if ($dropdown.is('.open')) {
                    $dropdown.trigger('hide.bs.dropdown').removeClass('open');
                }
            }
        };
        $el.data('globalHandlers', globalHandlers);
        $('html').on(globalHandlers);
    }

    Dropdown.prototype = $.fn.dropdown.Constructor.prototype;

    $(document).off('click.dropdown.data-api', toggleDropdown, Dropdown.prototype.toggle);
    Dropdown.prototype.toggle = _.wrap(Dropdown.prototype.toggle, function(func, event) {
        beforeClearMenus();
        var result = func.apply(this, _.rest(arguments));
        var href = $(this).attr('href');
        var selector = $(this).attr('data-target') || /#/.test(href) && href;
        var $parent = selector ? $(selector) : null;

        if (!$parent || $parent.length === 0) {
            $parent = $(this).parent();
        }
        if ($parent.hasClass('open')) {
            $parent.trigger('shown.bs.dropdown');
        }

        return result;
    });
    $(document)
        .on('click.dropdown.data-api', toggleDropdown, Dropdown.prototype.toggle)
        .on('tohide.bs.dropdown', function(e) {
            /**
             * Performs safe hide action for dropdown and triggers 'hide.bs.dropdown'
             * (the event 'tohide.bs.dropdown' have to be triggered on toggleDropdown or dropdown elements)
             */
            var $target = $(e.target);
            if ($target.is(toggleDropdown)) {
                $target = getParent($target);
            }
            if ($target.is('.dropdown.open')) {
                $target.trigger('hide.bs.dropdown');
            }
            $target.removeClass('open');
        });

    Dropdown.prototype.destroy = function() {
        var globalHandlers = this.data('globalHandlers');
        $('html').off(globalHandlers);
        this.removeData('dropdown');
        this.removeData('globalHandlers');
    };

    $.fn.dropdown = function(option) {
        return this.each(function() {
            var $this = $(this);
            var data = $this.data('dropdown');
            if (!data) {
                $this.data('dropdown', (data = new Dropdown(this)));
            }
            if (typeof option === 'string') {
                data[option].call($this);
            }
        });
    };

    $.fn.dropdown.Constructor = Dropdown;

    /**
     * Implements floating dropdown-menu (attaches the menu to body)
     * if a menu has data attribute "data-options="{&quot;html&quot;: true}""
     */
    (function() {
        function makeFloating($dropdownMenu) {
            var css = _.extend(_.pick($dropdownMenu.offset(), ['top', 'left']), {
                display: 'block',
                width: $dropdownMenu.outerWidth(),
                height: $dropdownMenu.outerHeight()
            });
            var $placeholder = $('<div class="dropdown-menu__placeholder"/>');
            $placeholder.data('related-menu', $dropdownMenu);
            $dropdownMenu
                .after($placeholder)
                .appendTo('body')
                .addClass('dropdown-menu__floating')
                .css(css)
                .one('mouseleave', function(e) {
                    $placeholder.trigger(e.type);
                });

            function toClose() {
                $placeholder.parent().trigger('tohide.bs.dropdown');
            }

            $placeholder.data('toCloseHandler', toClose)
                .parents().add(window).on('scroll resize', toClose);
        }

        function makeEmbedded($dropdownMenu, $placeholder) {
            $placeholder.parents().add(window)
                .off('scroll resize', $placeholder.data('toCloseHandler'));
            $dropdownMenu.removeClass('dropdown-menu__floating').removeAttr('style');
            $placeholder.after($dropdownMenu).remove();
        }

        $(document)
            .on('shown.bs.dropdown', '.dropdown', function() {
                var $dropdownMenu = $('>.dropdown-menu', this);
                var options = $dropdownMenu.data('options');
                if (options && options.html) {
                    makeFloating($dropdownMenu);
                }
            })
            .on('hide.bs.dropdown', '.dropdown.open', function() {
                var $placeholder = $('>.dropdown-menu__placeholder', this);
                var $dropdownMenu = $placeholder.data('related-menu');
                if ($dropdownMenu && $dropdownMenu.length) {
                    makeEmbedded($dropdownMenu, $placeholder);
                }
            });

        /**
         * Adds handler beforeClearMenus in front of original clearMenus handler
         * (for some reason bindFirst here does not work)
         */
        $(document).on('click.dropdown.data-api', beforeClearMenus);
        var clickEvents = $._data(document, 'events').click;
        var clearMenusHandler = _.find(clickEvents, function(event) {
            return event.handler.name === 'clearMenus';
        });
        clickEvents.splice(clickEvents.indexOf(clearMenusHandler), 0, clickEvents.pop());
    })();
});
