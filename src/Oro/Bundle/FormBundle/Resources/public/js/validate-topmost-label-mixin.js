define(function(require) {
    'use strict';

    var validateTopmostLabelMixin;
    var $ = require('jquery');
    var _ = require('underscore');

    function setInnerElementPosition(label) {
        if (!label.length) {
            return;
        }
        var offset = label.offset();
        var marginTop = label.css('marginTop').replace('px', '');
        var marginLeft = label.css('marginLeft').replace('px', '');
        label.children(':first').css({
            top: offset.top - marginTop + 'px',
            left: offset.left - marginLeft + 'px'
        });
    }
    /**
     * Mixin designed to make jquery validator error message visible over container element (e.g. dialog header)
     * @this validator
     */
    validateTopmostLabelMixin = {
        init: function() {
            var containerSelectors = '.ui-datepicker-dialog-is-below, .timepicker-dialog-is-below, .select2-drop-below';
            var widgetEvents = _.map(['datepicker:dialogReposition', 'select2:dialogReposition', 'showTimepicker',
                'hideTimepicker'], function(item) {
                return item + '.validate';
            });
            var labelIteratee = _.bind(function(i, element) {
                var $child = $(element).children(':first');
                if ($child.css('position') === 'fixed') {
                    setInnerElementPosition($(element));
                }
            }, this);
            $(this.currentForm).on(widgetEvents.join(' '), _.bind(function(e) {
                var label = this.errorsFor(e.target);
                if (label.length) {
                    setInnerElementPosition(label);
                } else {
                    $(e.target).parents(containerSelectors).parent().find('.validation-failed').each(labelIteratee);
                }
            }, this));
            var parentDialog = $(this.currentForm).closest('.ui-dialog');
            if (parentDialog.length === 1) {
                this.parentDialog = parentDialog;
                var dialogEvents = _.map(['dialogresize', 'dialogdrag', 'dialogreposition'], function(item) {
                    return item + '.validate';
                });
                parentDialog.on(dialogEvents.join(' '), _.bind(function(e) {
                    $(e.target).find('.validation-failed').each(labelIteratee);
                }, this));
            }
        },

        showLabel: function(element, message, label) {
            setInnerElementPosition(label.length ? label : this.errorsFor(element));
        },

        destroy: function() {
            if (this.parentDialog) {
                this.parentDialog.off('validate');
            }
        }
    };

    return validateTopmostLabelMixin;
});
