/*jslint evil: true, white: false, undef: false, nomen: false */

(function($) {
    /*
     * Lets not redefine datevariables, Prevent "Uncaught RangeError: Maximum call stack size exceeded"
     */
    $.ui.datevariables = $.ui.datevariables || {};
    if ($.ui.datevariables.version) {
        return;
    }

    /*
     * Extend jQueryUI, get it started with our version number
     */
    $.extend($.ui, {
        datevariables: {
            version: "1.0"
        }
    });

    /*
     * Datevariables manager.
     * Use the singleton instance of this class, $.datevariables, to interact with the datevariables.
     * Settings are maintained in an instance object,
     * allowing multiple different settings on the same page.
     */
    var Datevariables = function() {
        this.variables = [];
    };

    $.extend(Datevariables.prototype, {
        $input: null,
        $altInput: null,
        inst: null,

        /*
         * Create a new Datevariables instance
         */
        _newInst: function($input, o) {
            var tp_inst = new Datevariables(),
                fns = {},
                overrides, i;

            overrides = {
                beforeShow: function (input, dp_inst) {
                    if ($.isFunction(tp_inst._defaults.evnts.beforeShow)) {
                        return tp_inst._defaults.evnts.beforeShow.call($input[0], input, dp_inst, tp_inst);
                    }
                },
                onClose: function (dateText, dp_inst) {
                    if (tp_inst.timeDefined === true && $input.val() !== '') {
                        tp_inst._updateDateTime(dp_inst);
                    }
                    if ($.isFunction(tp_inst._defaults.evnts.onClose)) {
                        tp_inst._defaults.evnts.onClose.call($input[0], dateText, dp_inst, tp_inst);
                    }
                }
            };
            for (i in overrides) {
                if (overrides.hasOwnProperty(i)) {
                    fns[i] = o[i] || null;
                }
            }
            tp_inst._defaults = $.extend({}, this._defaults, o, overrides, {
                evnts:fns,
                datevariables: tp_inst // add datevariables as a property of datepicker: $.datepicker._get(dp_inst, 'datevariables');
            });

            tp_inst.$input = $input;
            tp_inst.$input.bind('focus', function() {
                tp_inst._onFocus();
            });

            return tp_inst;
        },

        /*
         * add our vars to the calendar
         */
        _addDatevariables: function(dp_inst) {
            var currDT = this.$altInput ? this.$input.val() + ' ' + this.$altInput.val() : this.$input.val();

            if (!this.inst) {
                this.inst = $.datepicker._getInst(this.$input[0]);
            }

            this._injectDateVariables();
        },

        _getDatevariablesByDatepart: function(datePart) {
            var dateVars = this.inst.settings.dateVars,
                result = [];

            // TODO: filter vars based on datePart

            return dateVars;
        },

        /*
         * generate and inject html for date variables into ui datepicker
         */
        _injectDateVariables: function() {
            var $dp = this.inst.dpDiv,
                o = this.inst.settings,
                tp_inst = this,
                currentDatePart = ($.datevariables.datePart == 1) ? 'value' : $.datevariables.datePart,
                dateVars = this._getDatevariablesByDatepart(currentDatePart);

            // Prevent displaying twice
            if ($dp.find("div.ui-datevariables-div").length === 0 && o.showDatevariables) {
                var html = '<div class="ui-datevariables-div'+ (o.isRTL? ' ui-datevariables-rtl' : '') +'"><dl>' +
                        '<dt class="ui_dvars_time_label">Date variables</dt>';

                for (var varCode in dateVars) {
                    html += '<dd class="ui_dvars_content">' +
                        '<a class="ui_dvariable" href="#" data-code="' + varCode + '">{{ ' + o.dateVars[varCode] + ' }}</a></dd>';
                }
                html += '</dl></div>';

                var $tp = $(html);
                if (o.varsOnly === true) {
                    $tp.prepend('<div class="ui-widget-header ui-helper-clearfix ui-corner-all">' + '<div class="ui-datepicker-title">' + o.timeOnlyTitle + '</div>' + '</div>');
                    $dp.find('.ui-datepicker-header, .ui-datepicker-calendar').hide();
                }

                // inject datevariables into datepicker
                var $buttonPanel = $dp.find('.ui-datepicker-buttonpane');
                if ($buttonPanel.length) {
                    $buttonPanel.after($tp);
                } else {
                    $dp.append($tp);
                }

                $(".ui-datevariables-div a.ui_dvariable").click(function(e) {
                    var variable = this.text,
                        code = this.dataset.code;
                    tp_inst.$input.val(variable);
                    tp_inst.$input.next('input[type=hidden]').val(code);

                    tp_inst.$input.trigger("change");
                    e.preventDefault();
                });
            }
        },

        /*
         * update our input with the new date time..
         */
        _updateDateTime: function(dp_inst) {
            dp_inst = this.inst || dp_inst;
            var dt = $.datepicker._daylightSavingAdjust(new Date(dp_inst.selectedYear, dp_inst.selectedMonth, dp_inst.selectedDay)),
                dateFmt = $.datepicker._get(dp_inst, 'dateFormat'),
                formatCfg = $.datepicker._getFormatConfig(dp_inst),
                timeAvailable = dt !== null && this.timeDefined;
            this.formattedDate = $.datepicker.formatDate(dateFmt, (dt === null ? new Date() : dt), formatCfg);
            var formattedDateTime = this.formattedDate;

            // if a slider was changed but datepicker doesn't have a value yet, set it
            if(dp_inst.lastVal==""){
                dp_inst.currentYear=dp_inst.selectedYear;
                dp_inst.currentMonth=dp_inst.selectedMonth;
                dp_inst.currentDay=dp_inst.selectedDay;
            }

            /*
             * remove following lines to force every changes in date picker to change the input value
             * Bug descriptions: when an input field has a default value, and click on the field to pop up the date picker.
             * If the user manually empty the value in the input field, the date picker will never change selected value.
             */
            //if (dp_inst.lastVal !== undefined && (dp_inst.lastVal.length > 0 && this.$input.val().length === 0)) {
            //	return;
            //}

            if (this._defaults.timeOnly === true) {
                formattedDateTime = this.formattedTime;
            } else if (this._defaults.timeOnly !== true && (this._defaults.alwaysSetTime || timeAvailable)) {
                formattedDateTime += this._defaults.separator + this.formattedTime + this._defaults.timeSuffix;
            }

            this.formattedDateTime = formattedDateTime;

            if (!this._defaults.showDatevariables) {
                this.$input.val(this.formattedDate);
            } else if (this.$altInput && this._defaults.altFieldTimeOnly === true) {
                this.$altInput.val(this.formattedTime);
                this.$input.val(this.formattedDate);
            } else if (this.$altInput) {
                this.$input.val(formattedDateTime);
                var altFormattedDateTime = '',
                    altSeparator = this._defaults.altSeparator ? this._defaults.altSeparator : this._defaults.separator,
                    altTimeSuffix = this._defaults.altTimeSuffix ? this._defaults.altTimeSuffix : this._defaults.timeSuffix;

                if (this._defaults.altFormat) altFormattedDateTime = $.datepicker.formatDate(this._defaults.altFormat, (dt === null ? new Date() : dt), formatCfg);
                else altFormattedDateTime = this.formattedDate;
                if (altFormattedDateTime) altFormattedDateTime += altSeparator;
                if (this._defaults.altTimeFormat) altFormattedDateTime += $.datepicker.formatTime(this._defaults.altTimeFormat, this, this._defaults) + altTimeSuffix;
                else altFormattedDateTime += this.formattedTime + altTimeSuffix;
                this.$altInput.val(altFormattedDateTime);
            } else {
                this.$input.val(formattedDateTime);
            }

            this.$input.trigger("change");
        },

        _onFocus: function() {
            if (!this.$input.val() && this._defaults.defaultValue) {
                this.$input.val(this._defaults.defaultValue);
                var inst = $.datepicker._getInst(this.$input.get(0)),
                    tp_inst = $.datepicker._get(inst, 'datevariables');
                if (tp_inst) {
                    if (tp_inst._defaults.timeOnly && (inst.input.val() != inst.lastVal)) {
                        try {
                            $.datepicker._updateDatepicker(inst);
                        } catch (err) {
                            $.datevariables.log(err);
                        }
                    }
                }
            }
        }
    });

    $.fn.extend({
        /*
         * shorthand just to use datevariables.
         */
        datevariables: function(o) {
            o = o || {};
            var tmp_args = Array.prototype.slice.call(arguments);

            if (typeof o == 'object') {
                return this.each(function() {
                    var $t = $(this);
                    $t.datetimepicker($.datevariables._newInst($t, o)._defaults);
                });
            }

            return $(this).each(function() {
                $.fn.datetimepicker.apply($(this), tmp_args);
            });
        }
    });

    $.datepicker._prev_updateDatepicker = $.datepicker._updateDatepicker;
    $.datepicker._updateDatepicker = function(inst) {

        // don't popup the datepicker if there is another instance already opened
        var input = inst.input[0];
        if ($.datepicker._curInst && $.datepicker._curInst != inst && $.datepicker._datepickerShowing && $.datepicker._lastInput != input) {
            return;
        }

        if (typeof(inst.stay_open) !== 'boolean' || inst.stay_open === false) {

            this._prev_updateDatepicker(inst);

            // Reload the control when changing something in the input text field.
            var tp_inst = this._get(inst, 'datevariables');
            if (tp_inst) {
                tp_inst._addDatevariables(inst);
            }
        }
    };

    /*
     * Create a Singleton Insance
     */
    $.datevariables = new Datevariables();

    /**
     * Log error or data to the console during error or debugging
     * @param  Object err pass any type object to log to the console during error or debugging
     * @return void
     */
    $.datevariables.log = function(err){
        if(window.console)
            console.log(err);
    };

    $.datepicker.parseDate = function(format, value, settings) {
        var date;
        try {
            date = this._base_parseDate(format, value, settings);
        } catch (err) {
            $.timepicker.log("Error parsing the date string: " + err + "\ndate string = " + value + "\ndate format = " + format);
        }
        return date;
    };

    $.datevariables.version = "1.0";

})(jQuery);
