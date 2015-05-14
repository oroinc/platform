/*global define*/
define([
    'jquery',
    'bootstrap'
], function ($) {
    'use strict';

    /**
     * Override for Dropdown constructor
     *  - added destroy method, which removes event handlers from <html /> node
     *
     * @param {HTMLElement} element
     * @constructor
     */
    function Dropdown(element) {
        var $el, globalHandlers;
        $el = $(element).on('click.dropdown.data-api', this.toggle);
        globalHandlers = {
            'click.dropdown.data-api': function () {
                $el.parent().removeClass('open');
            }
        };
        $el.data('globalHandlers', globalHandlers);
        $('html').on(globalHandlers);
    }

    Dropdown.prototype = $.fn.dropdown.Constructor.prototype;
    Dropdown.prototype.destroy = function () {
        var globalHandlers = this.data('globalHandlers');
        $('html').off(globalHandlers);
        this.removeData('dropdown');
        this.removeData('globalHandlers');
    };


    /*jslint ignore:start*/
    $.fn.dropdown = function (option) {
        return this.each(function () {
            var $this = $(this)
                , data = $this.data('dropdown')
            if (!data) $this.data('dropdown', (data = new Dropdown(this)))
            if (typeof option == 'string') data[option].call($this)
        })
    }

    $.fn.dropdown.Constructor = Dropdown
    /*jslint ignore:end*/

    /**
     * fix endless loop
     * Based on https://github.com/Khan/bootstrap/commit/378ab557e24b861579d2ec4ce6f04b9ea995ab74
     * Updated to support two modals on page
     */
    $.fn.modal.Constructor.prototype.enforceFocus = function () {
        var that = this;
        $(document)
            .off('focusin.modal') // guard against infinite focus loop
            .on('focusin.modal', function safeSetFocus(e) {
                if (that.$element[0] !== e.target && !that.$element.has(e.target).length) {
                    $(document).off('focusin.modal');
                    that.$element.focus();
                    $(document).on('focusin.modal', safeSetFocus);
                }
            });
    }

    $.fn.typeahead.Constructor.prototype.render = function (items) {
        var that = this
        items = $(items).map(function (i, item) {
            if (item.item.dialog_config) {
                i = $('<li><a href="javascript: void(0);" class=" no-hash" data-id="95" data-url="/app_dev.php/email/create" title="Send email" data-page-component-module="oroui/js/app/components/widget-component" data-page-component-options="{&quot;type&quot;:&quot;dialog&quot;,&quot;multiple&quot;:true,&quot;refresh-widget-alias&quot;:&quot;activity-list-widget&quot;,&quot;options&quot;:{&quot;alias&quot;:&quot;email-dialog&quot;,&quot;dialogOptions&quot;:{&quot;title&quot;:&quot;Send email&quot;,&quot;allowMaximize&quot;:true,&quot;allowMinimize&quot;:true,&quot;dblclick&quot;:&quot;maximize&quot;,&quot;maximizedHeightDecreaseBy&quot;:&quot;minimize-bar&quot;,&quot;width&quot;:1000}},&quot;createOnEvent&quot;:&quot;click&quot;}"><i class="icon-envelope hide-text">Send email</i> '+that.highlighter(item.key)+'</a></li>')
            } else {
                i = $(that.options.item).attr('data-value', item.key)
                i.find('a').html(that.highlighter(item.key))
            }

            return i[0]
        })

        items.first().addClass('active')
        this.$menu.html(items)
        return this
    }
});
