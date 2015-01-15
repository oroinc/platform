/*global define*/
define(['jquery'], function ($) {
    'use strict';
    $.ajaxSetup({
        headers: {
            'X-CSRF-Header': 1
        }
    });
    $.expr[':'].parents = function (a, i, m) {
        return $(a).parents(m[3]).length < 1;
    };
    // used to indicate app's activity, such as AJAX request or redirection, etc.
    $.isActive = $.proxy(function (flag) {
        if ($.type(flag) !== 'undefined') {
            this.active = flag;
        }
        return $.active || this.active;
    }, {active: false});

    $.fn.extend({
        // http://stackoverflow.com/questions/4609405/set-focus-after-last-character-in-text-box
        focusAndSetCaretAtEnd: function () {
            if (!this.length)
                return;
            var elem = this[0], elemLen = elem.value.length;
            // For IE Only
            if (document.selection) {
                // Set focus
                $(elem).focus();
                // Use IE Ranges
                var oSel = document.selection.createRange();
                // Reset position to 0 & then set at end
                oSel.moveStart('character', -elemLen);
                oSel.moveStart('character', elemLen);
                oSel.moveEnd('character', 0);
                oSel.select();
            }
            else if (elem.selectionStart || elem.selectionStart == '0') {
                // Firefox/Chrome
                elem.selectionStart = elemLen;
                elem.selectionEnd = elemLen;
                $(elem).focus();
            } // if
        },

        /**
         * Sets focus on first form field
         */
        focusFirstInput: function () {
            var $autoFocus,
                $input = this.find(':input:visible, [data-focusable]')
                    .not(':checkbox, :radio, :button, :submit, :disabled, :file');
            $autoFocus = $input.filter('[autofocus]');
            ($autoFocus.length ? $autoFocus : $input).first().focus();
        },

        /*
         * getStyleObject Plugin for jQuery JavaScript Library
         * From: http://upshots.org/?p=112
         *
         * Copyright: Unknown, see source link
         * Plugin version by Dakota Schneider (http://hackthetruth.org)
         */
        getStyleObject: function(){
            var dom = this.get(0);
            var style;
            var returns = {};
            if(window.getComputedStyle){
                var camelize = function(a,b){
                    return b.toUpperCase();
                }
                style = window.getComputedStyle(dom, null);
                for(var i=0;i<style.length;i++){
                    var prop = style[i];
                    var camel = prop.replace(/\-([a-z])/g, camelize);
                    var val = style.getPropertyValue(prop);
                    returns[camel] = val;
                }
                return returns;
            }
            if(dom.currentStyle){
                style = dom.currentStyle;
                for(var prop in style){
                    returns[prop] = style[prop];
                }
                return returns;
            }
            return this.css();
        },

        cloneWithStyles: function () {
            // strange but only that works
            var result = $('<div>');
            this.each(function () {
                var el = $(this),
                    clone = el.clone().html('');
                for (var i = 0; i < this.childNodes.length; i++) {
                    var node = this.childNodes[i];
                    if (node.nodeType === 1) { // ELEMENT_NODE
                        clone[0].appendChild($(node).cloneWithStyles()[0]);
                    } else {
                        // all other nodes don't need any attention
                        clone[0].appendChild(node.cloneNode());
                    }
                }
                clone.css(el.getStyleObject());
                // IE fix
                if (clone.is("[class*='icon-']")) {
                    clone.css({
                        'font-family' : ''
                    });
                }
                result.append(clone);
            });
            return result.find('>*');
        },

        focus: (function(orig) {
            return function() {
                var $elem = $(this);
                if (!arguments.length && $elem.attr('data-focusable')) {
                    // the element has own implementation to set focus
                    $elem.triggerHandler('set-focus');
                    return $elem;
                } else {
                    return orig.apply(this, arguments);
                }
            };
        })($.fn.focus)
    });

    return $;
});
