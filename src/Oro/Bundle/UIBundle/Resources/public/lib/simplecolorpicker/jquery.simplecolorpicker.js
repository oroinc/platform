/*
 * Very simple jQuery Color Picker
 * https://github.com/tkrotoff/jquery-simplecolorpicker
 *
 * Copyright (C) 2012-2013 Tanguy Krotoff <tkrotoff@gmail.com>
 *
 * Licensed under the MIT license
 */

(function($) {
  'use strict';

  /**
   * Constructor.
   */
  var SimpleColorPicker = function(select, options) {
    this.init('simplecolorpicker', select, options);
  };

  /**
   * SimpleColorPicker class.
   */
  SimpleColorPicker.prototype = {
    constructor: SimpleColorPicker,

    init: function(type, select, options) {
      var self = this;

      self.type = type;

      self.$select = $(select);
      self.$select.hide();

      self.options = $.extend({}, $.fn.simplecolorpicker.defaults, options);

      self.$colorList = null;

      var selectedVal = self.$select.val();

      if (self.options.picker === true) {
        var selectText = self.$select.is('select') ? self.$select.find('> option:selected').text() : this.getSelectedText(self.options.data);
        self.$icon = $('<span class="simplecolorpicker icon"'
                     + ' title="' + selectText + '"'
                     + ' style="background-color: ' + selectedVal + ';"'
                     + ' role="button" tabindex="0">'
                     + '</span>').insertAfter(self.$select);
        self.$icon.on('click.' + self.type, $.proxy(self.showPicker, self));

        self.$picker = $('<span class="simplecolorpicker picker ' + self.options.theme + '"></span>').appendTo(document.body);
        self.$colorList = self.$picker;

        // Hide picker when clicking outside
        $(document).on('mousedown.' + self.type, $.proxy(self.hidePicker, self));
        self.$picker.on('mousedown.' + self.type, $.proxy(self.mousedown, self));
      } else {
        self.$inline = $('<span class="simplecolorpicker inline ' + self.options.theme + '"></span>').insertAfter(self.$select);
        self.$colorList = self.$inline;
      }

      // Build the list of colors
      // <span class="color selected" title="Green" style="background-color: #7bd148;" role="button"></span>
      var buildItemFunc = function($option, isSelect) {
        if (!isSelect && (($.isArray($option) && !$option.length) || $.isEmptyObject($option))) {
            // Vertical break, like hr
            self.$colorList.append('<span class="vr"></span>');
            return;
        }

        var color = isSelect ? $option.val() : ($option.id || '');
        var bgColor = !color && self.options.emptyColor ? self.options.emptyColor : color;

        var isSelected = isSelect ? $option.is(':selected') : ($option.selected ? true : false);
        var isDisabled = isSelect ? $option.is(':disabled') : ($option.disabled ? true : false);

        var selected = '';
        if (isSelected === true) {
          selected = ' data-selected';
        }

        var disabled = '';
        if (isDisabled === true) {
          disabled = ' data-disabled';
        }

        var title = '';
        if (isDisabled === false) {
          title = ' title="' + (isSelect ? $option.text() : $option.text) + '"';
        }

        var role = '';
        if (isDisabled === false) {
          role = ' role="button" tabindex="0"';
        }

        var cssClass = isSelect ? $option.prop('class') : $option.class;
        cssClass = cssClass ? 'color ' + cssClass : 'color';

        var $colorSpan = $('<span class="' + cssClass + '"'
                         + title
                         + ' style="color: ' + self.getContrastColor(bgColor) + '; background-color: ' + bgColor + ';"'
                         + ' data-color="' + color + '"'
                         + selected
                         + disabled
                         + role + '>'
                         + '</span>');

        self.$colorList.append($colorSpan);
        $colorSpan.on('click.' + self.type, $.proxy(self.colorSpanClicked, self));

        if (!isSelect) {
            return;
        }
        var $next = $option.next();
        if ($next.is('optgroup') === true) {
          // Vertical break, like hr
          self.$colorList.append('<span class="vr"></span>');
        }
      };

      if (self.$select.is('select')) {
          self.$select.find('> option').each(function () {
              buildItemFunc($(this), true);
          });
      } else if (!self.options.table) {
          var selected = null, selectedId = null, matched = null;
          $.each(self.options.data, function(index, value) {
              if (selected === null && value.selected) {
                  selected = index;
                  selectedId = value.id;
              }
              if (matched === null && (
                  (!value.id && !selectedVal) ||
                  (value.id && selectedVal && value.id.toLowerCase() === selectedVal.toLowerCase())
              )) {
                  matched = index;
              }
          });
          $.each(self.options.data, function(index, value) {
              if (value.selected && selected !== null && matched !== null && matched !== selected) {
                  value.selected = false;
              } else if (!value.selected && matched !== null && matched === index) {
                  value.selected = true;
              }
              buildItemFunc(value, false);
          });
      } else {
          var data = [];
          if (selectedVal) {
              $.each(JSON.parse(selectedVal), function(index, value) {
                  data.push({id: value, text: value});
              });
          }
          $.each(data, function(index, value) {
              buildItemFunc(value, false);
          });
      }
    },

    /**
     * Changes the selected color.
     *
     * @param color the hexadecimal color to select, ex: '#fbd75b'
     */
    selectColor: function(color) {
      var self = this;

      var $colorSpan = self.$colorList.find('> span.color').filter(function() {
        return $(this).data('color').toLowerCase() === color.toLowerCase();
      });

      if ($colorSpan.length > 0) {
        self.selectColorSpan($colorSpan);
      } else {
        console.error("The given color '" + color + "' could not be found");
      }
    },

    /**
     * Sets empty color.
     *
     * @param color the hexadecimal color to select, ex: '#fbd75b'
     */
    setEmptyColor: function(color) {
      var self = this;

      var $colorSpan = self.$colorList.find('> span.color').filter(function() {
        return $(this).data('color').toLowerCase() === self.options.emptyColor.toLowerCase();
      });

      if ($colorSpan.length > 0) {
        $colorSpan.data('color', color);
        $colorSpan.css({'backgroung-color': color, 'color': this.getContrastColor(color)});
        self.options.emptyColor = color;
      } else {
        console.error("The empty color could not be found");
      }
    },

    /**
     * Replace the color.
     * This method can be used if `options.table` is true
     *
     * @param {string} oldColor the hexadecimal color to be replaced, ex: '#fbd75b'
     * @param {string} newColor the hexadecimal color to replace, ex: '#fbd75b'
     */
    replaceColor: function(oldColor, newColor) {
      var self = this;

      var $colorSpan = self.$colorList.find('> span.color').filter(function() {
        return $(this).data('color').toLowerCase() === oldColor.toLowerCase();
      });

      if ($colorSpan.length > 0) {
        $colorSpan.data('color', newColor);
        $colorSpan.css('background-color', newColor);
        $colorSpan.prop('title', newColor);
        // Change HTML select value
        var val = JSON.parse(this.$select.val()),
            foundIndex = -1;
          $.each(val, function(index, value) {
              if (value.toLowerCase() === oldColor.toLowerCase()) {
                  foundIndex = index;
              }
          });
          if (foundIndex !== -1) {
              val[foundIndex] = newColor;
              this.$select.val(JSON.stringify(val));
          }
      } else {
        console.error("The given color '" + oldColor + "' could not be found");
      }
    },

    showPicker: function() {
      var pos = this.$icon.offset();
      this.$picker.css({
        // Remove some pixels to align the picker icon with the icons inside the dropdown
        left: pos.left - 6,
        top: pos.top + this.$icon.outerHeight()
      });

      this.$picker.show(this.options.pickerDelay);
    },

    hidePicker: function() {
      this.$picker.hide(this.options.pickerDelay);
    },

    /**
     * Selects the given span inside $colorList.
     *
     * The given span becomes the selected one.
     * It also changes the HTML select value, this will emit the 'change' event.
     */
    selectColorSpan: function($colorSpan) {
      var color = $colorSpan.data('color');
      var title = $colorSpan.prop('title');

      if (!this.options.table) {
        // Mark this span as the selected one
        $colorSpan.siblings().removeAttr('data-selected');
        $colorSpan.attr('data-selected', '');
        $colorSpan.css('color', this.getContrastColor(color || this.options.emptyColor));
      }

      if (this.options.picker === true) {
        this.$icon.css('background-color', color);
        this.$icon.prop('title', title);
        this.hidePicker();
      }

      // Change HTML select value
      if (!this.options.table) {
          this.$select.val(color);
      }
    },

    /**
     * The user clicked on a color inside $colorList.
     */
    colorSpanClicked: function(e) {
      // When a color is clicked, make it the new selected one (unless disabled)
      if ($(e.target).is('[data-disabled]') === false) {
        this.selectColorSpan($(e.target));
        this.$select.trigger('change');
      }
    },

    /**
     * Prevents the mousedown event from "eating" the click event.
     */
    mousedown: function(e) {
      e.stopPropagation();
      e.preventDefault();
    },

    destroy: function() {
      if (this.options.picker === true) {
        this.$icon.off('.' + this.type);
        this.$icon.remove();
        $(document).off('.' + this.type);
      }

      this.$colorList.off('.' + this.type);
      this.$colorList.remove();

      this.$select.removeData(this.type);
      this.$select.show();
    },

    getSelectedText: function(data) {
        var text = null;
        $.each(data, function(index, value) {
            if (value.selected) {
                text = value.text;
            }
        });
        return text;
    },

    /**
     * Converts a hex string to an RGB object
     *
     * @param {string} hex A color in six-digit hexadecimal form.
     * @returns {Object|null}
     */
    hex2rgb: function (hex) {
        var result = /^#([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    },

    /**
     * Calculates contrast color
     * @see http://www.w3.org/WAI/ER/WD-AERT/#color-contrast
     *
     * @param {string} hex A color in six-digit hexadecimal form.
     * @returns {string} Calculated sufficient contrast color, black or white.
     *                   If the given color is invalid or cannot be parsed, returns black.
     */
    getContrastColor: function (hex) {
        var rgb = this.hex2rgb(hex),
            yiq = rgb ? ((299 * rgb.r + 587 * rgb.g + 114 * rgb.b) / 1000) : 255,
            clrDiff = rgb ? (rgb.r + rgb.g + rgb.b) : 1000;
        return yiq > 125 && clrDiff > 500 ? '#000000' : '#FFFFFF';
    }
  };

  /**
   * Plugin definition.
   * How to use: $('#id').simplecolorpicker()
   */
  $.fn.simplecolorpicker = function(option) {
    var args = $.makeArray(arguments);
    args.shift();

    // For HTML element passed to the plugin
    return this.each(function() {
      var $this = $(this),
        data = $this.data('simplecolorpicker'),
        options = typeof option === 'object' && option;
      if (data === undefined) {
        $this.data('simplecolorpicker', (data = new SimpleColorPicker(this, options)));
      }
      if (typeof option === 'string') {
        data[option].apply(data, args);
      }
    });
  };

  /**
   * Default options.
   */
  $.fn.simplecolorpicker.defaults = {
    // No theme by default
    theme: '',

    // Show the picker or make it inline
    picker: false,

    // Animation delay in milliseconds
    pickerDelay: 0,

    // A color for empty option
    emptyColor: null,

    // The list of options in case when the picker is applied for non SELECT
    // array of {id: ..., text: ..., class: ..., selected: ..., disabled: ...}
    data: null,

    // Allows to use this control to edit any color in the list, rather than select one color
    // Note that this control has not integrated color picker and it should be added outside
    // The 'picker' property must be false then this property is true,
    // also 'pickerDelay' and 'data' properties are ignored in this case
    table: false
  };
})(jQuery);
