/*jslint evil: true, white: false, undef: false, nomen: false */
/* global define */
define(['jquery', 'underscore', 'oro/translator', 'oro/layout', 'jquery-ui'],
    function ($, _, __, layout) {
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
        var Datevariables = function () {
            this.variables = [];
        };

        $.extend(Datevariables.prototype, {
            $input: null,
            $altInput: null,
            inst: null,

            /*
             * Create a new Datevariables instance
             */
            _newInst: function ($input, o) {
                var dvInst = new Datevariables(),
                    fns = {};

                dvInst._defaults = $.extend({}, this._defaults, o, {
                    evnts: fns,
                    datevariables: dvInst // add datevariables as a property of datepicker: $.datepicker._get(dp_inst, 'datevariables');
                });

                dvInst.$input = $input;
                dvInst.$input.bind('focus', function () {
                    dvInst._onFocus();
                });

                return dvInst;
            },

            _addDatevariables: function () {
                if (!this.inst) {
                    this.inst = $.datepicker._getInst(this.$input[0]);
                }

                this._injectDateVariables();
            },

            _getDatevariablesByDatepart: function (datePart) {
                var dateVars = this.inst.settings.dateVars;
                return dateVars[datePart] ? dateVars[datePart] : dateVars['value'];
            },

            /*
             * generate and inject html for date variables into ui datepicker
             */
            _injectDateVariables: function () {
                var $dp = this.inst.dpDiv,
                    o = this.inst.settings,
                    dvInst = this,
                    currentDatePart = this.inst.settings.part,
                    dateVars = this._getDatevariablesByDatepart(currentDatePart),
                    tooltipTemplate = _.template('<i class="icon-info-sign" data-content="<%- content %>"' +
                        ' data-placement="top" data-toggle="popover" data-original-title="<%- title %>"></i>');

                // Prevent displaying twice
                if ($dp.find("div.ui-datevariables-div").length === 0 && o.showDatevariables) {
                    var htmlTemplate = _.template('<div class="ui-datevariables-div <%- attributes %>">' +
                        '<b><%- title %></b><%= tooltipHTML %><ul>' +
                        '<% _.each(dateVars, function(dateVariable, varCode) { %>' +
                        '<li><a class="ui_dvariable" href="#" data-code="<%- varCode %>"><%- dateVariable %></a></li>' +
                        '<% }); %>' +
                        '</ul></div>'
                    );

                    var $tp = $(htmlTemplate({
                        attributes:  o.isRTL ? ' ui-datevariables-rtl' : '',
                        title:       __('oro.filter.date.variable.title'),
                        tooltipHTML: tooltipTemplate({content: __('oro.filter.date.variable.tooltip'), title:__('oro.filter.date.variable.tooltip_title')}),
                        dateVars:    dateVars
                    }));

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
                    layout.initPopover($dp);

                    $(".ui-datevariables-div a.ui_dvariable").click(function (e) {
                        var variable = this.text;
                        dvInst.$input.val(variable);
                        dvInst.$input.trigger("change");
                        e.preventDefault();
                    });
                }
            },

            _onFocus: function () {
                if (!this.$input.val() && this._defaults.defaultValue) {
                    this.$input.val(this._defaults.defaultValue);
                    var inst = $.datepicker._getInst(this.$input.get(0)),
                        dvInst = $.datepicker._get(inst, 'datevariables');
                    if (dvInst) {
                        if (dvInst._defaults.timeOnly && (inst.input.val() != inst.lastVal)) {
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
            datevariables: function (o) {
                o = o || {};
                var tmpArgs = Array.prototype.slice.call(arguments);

                if (typeof o == 'object') {
                    return this.each(function () {
                        var $t = $(this);
                        $t.datetimepicker($.datevariables._newInst($t, o)._defaults);
                    });
                }

                return $(this).each(function () {
                    $.fn.datetimepicker.apply($(this), tmpArgs);
                });
            }
        });

        $.datepicker._prev_updateDatepicker = $.datepicker._updateDatepicker;
        $.datepicker._updateDatepicker = function (inst) {
            // don't popup the datepicker if there is another instance already opened
            var input = inst.input[0];
            if ($.datepicker._curInst && $.datepicker._curInst != inst && $.datepicker._datepickerShowing && $.datepicker._lastInput != input) {
                return;
            }

            if (typeof(inst.stay_open) !== 'boolean' || inst.stay_open === false) {
                this._prev_updateDatepicker(inst);
                // Reload the control when changing something in the input text field.
                var dvInst = this._get(inst, 'datevariables');
                if (dvInst) {
                    dvInst._addDatevariables(inst);
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
        $.datevariables.log = function (err) {
            if (window.console) {
                console.log(err);
            }
        };

        $.datepicker.parseDate = function (format, value, settings) {
            var date;
            try {
                date = this._base_parseDate(format, value, settings);
            } catch (err) {
            }
            return date;
        };

        $.datevariables.version = "1.0";
    }
);
