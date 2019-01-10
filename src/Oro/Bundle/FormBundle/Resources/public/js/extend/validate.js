define(function(require) {
    'use strict';

    var $ = require('jquery.validate');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var tools = require('oroui/js/tools');
    var logger = require('oroui/js/tools/logger');
    var validationHandler = require('oroform/js/optional-validation-groups-handler');
    var validateTopmostLabelMixin = require('oroform/js/validate-topmost-label-mixin');
    var error = require('oroui/js/error');

    var original = _.pick($.validator.prototype, 'init', 'showLabel', 'defaultShowErrors');

    /**
     * Collects all ancestor elements that have validation rules
     *
     * @param {Element|jQuery} element
     * @returns {Array.<Element>} sorted in order from form element to input element
     */
    function validationHolders(element) {
        var elems = [];
        var $el = $(element);
        var form = $el.parents('form').first();
        // instance of validator
        var validator = $(form).data('validator');
        if (validator instanceof $.validator) {
            elems = _.filter($el.add($el.parentsUntil(form)).add(form).toArray(), function(el) {
                var $el = $(el);
                // is it current element or the first in a group of elements or the first visible one
                return $el.data('validation') && ($el.is(element) || validator.elementsOf($el).first().is(element));
            });
        }

        return elems;
    }

    /**
     * Goes across ancestor elements (including itself) and collects validation rules
     *
     * @param {Element|jQuery} element
     * @return {Object} key name of validation rule, value is its options
     */
    function validationsOf(element) {
        var validations = _.map(validationHolders(element), function(el) {
            return filterElementValidators(el);
        });
        validations.unshift({});
        return _.extend.apply(null, validations);
    }

    /**
     * Looks for ancestor element (including itself), whose validation rule was violated
     *
     * @param {Element|jQuery} element
     * @param {string=} method by default reads methods name from element's 'data-violated' property
     * @returns {Element}
     */
    function validationBelongs(element, method) {
        method = method || $(element).data('violated');
        return _.find(validationHolders(element).reverse(), function(el) {
            return $(el).data('validation').hasOwnProperty(method);
        }) || element;
    }

    /**
     * Looks for validation error message holder
     *
     * @param {Element} element
     * @returns {jQuery}
     */
    function getErrorTarget(element) {
        var $target = $(validationBelongs(element));
        var $widgetContainer = $target.inputWidget('getContainer');
        if ($widgetContainer) {
            $target = $widgetContainer;
        }
        var $parent = $target.parent();
        if ($parent.is('.input-append, .input-prepend')) {
            $target = $parent;
        }
        var $validateGroup;
        if ($target.is(element) && ($validateGroup = $target.closest('.validate-group')).length) {
            // the element inside validate group -- pass delegate validation to it
            $target = $validateGroup;
        }

        return $target;
    }

    /**
     * Gets element after which it needs insert error message
     *
     * @param {Element} element
     * @returns {jQuery}
     */
    function getErrorPlacement(element) {
        var $targetElem = getErrorTarget(element);
        var $errorHolder = $targetElem.closest('.fields-row');

        if (!$errorHolder.length) {
            return $targetElem;
        }

        return $($errorHolder.find('.fields-row-error').get(0) || $errorHolder);
    }

    /**
     * @param {jQuery} el
     * @return {Object}
     */
    function filterElementValidators(el) {
        var $el = $(el);
        var validation = $el.data('validation');

        if (!$el.is(':input')) {
            // remove NotNull/NotBlank from not :input
            delete validation.NotNull;
            delete validation.NotBlank;
        }

        return validation;
    }

    // turn off adding rules from attributes
    $.validator.attributeRules = function() {
        return {};
    };

    // turn off adding rules from class
    $.validator.classRules = function() {
        return {};
    };

    // substitute data rules reader
    $.validator.dataRules = function(element) {
        var rules = {};
        _.each(validationsOf(element), function(param, method) {
            if ($.validator.methods[method]) {
                rules[method] = {param: param};
            } else if ($(element.form).data('validator').settings.debug) {
                error.showErrorInConsole('Validation method "' + method + '" does not exist');
            }
        });
        // make sure required validators are at front
        _.each(['NotNull', 'NotBlank'], function(name) {
            if (rules[name]) {
                var _rules = {};
                _rules[name] = rules[name];
                delete rules[name];
                rules = $.extend(_rules, rules);
            }
        });
        return rules;
    };

    $.validator.prototype.check = _.wrap($.validator.prototype.check, function(check, element) {
        if (!element.name) {
            // add temporary elements names to support validation for frontend elements
            element.name = _.uniqueId('temp-validation-name-');
        }

        var isValid = check.call(this, element);

        if (isValid) {
            validateTopmostLabelMixin.validationSuccessHandler.call(this, element);
        }

        return isValid;
    });

    $.validator.prototype.valid = _.wrap($.validator.prototype.valid, function(valid) {
        var isValid = valid.call(this);
        if (isValid) {
            // remove temporary elements names in case valid form, before form submit
            $(this.currentForm)
                .find('[name^="temp-validation-name-"]')
                .each(function() {
                    $(this).removeAttr('name');
                });
        }
        return isValid;
    });

    $.validator.prototype.elements = _.wrap($.validator.prototype.elements, function(func) {
        var $additionalElements = $(this.currentForm).find(':input[data-validate-element]');
        return func.apply(this, _.rest(arguments)).add($additionalElements);
    });

    // saves name of validation rule which is violated
    $.validator.prototype.formatAndAdd = _.wrap($.validator.prototype.formatAndAdd, function(func, element, rule) {
        $(element).data('violated', rule.method);
        return func.apply(this, _.rest(arguments));
    });

    $.validator.prototype.destroy = _.wrap($.validator.prototype.destroy, function(originDestroy) {
        validateTopmostLabelMixin.destroy.call(this);
        originDestroy.call(this);
    });

    // fixes focus on select2 element and problem with focus on hidden inputs
    $.validator.prototype.focusInvalid = _.wrap($.validator.prototype.focusInvalid, function(func) {
        if (!this.settings.focusInvalid) {
            return func.apply(this, _.rest(arguments));
        }

        var $elem = $(this.findLastActive() || (this.errorList.length && this.errorList[0].element) || []);
        var $firstValidationError = $('.validation-failed').filter(':visible').first();

        if ($elem.is('.select2[type=hidden]')) {
            $elem.parent().find('input.select2-focusser')
                .focus()
                .trigger('focusin');
        } else if (!$elem.filter(':visible').length && $firstValidationError.length) {
            var $scrollableContainer = $firstValidationError.closest('.scrollable-container');
            var scrollTop = $firstValidationError.position().top + $scrollableContainer.scrollTop();

            $scrollableContainer.animate({
                scrollTop: scrollTop
            }, scrollTop / 2);
        } else {
            return func.apply(this, _.rest(arguments));
        }
    });

    $.validator.prototype.resetForm = _.wrap($.validator.prototype.resetForm, function(resetForm) {
        resetForm.apply(this, _.rest(arguments));
        this.collectPristineValues();
    });

    _.extend($.validator.prototype, {
        init: function() {
            validationHandler.initialize($(this.currentForm));
            validateTopmostLabelMixin.init.call(this);
            $(this.currentForm).on('content:changed', function(event) {
                validationHandler.initializeOptionalValidationGroupHandlers($(event.target));
            }).on('disabled', function(e) {
                this.hideElementErrors(e.target);
            }.bind(this));

            original.init.call(this);

            this.bindInitialErrors();

            // Following call is deferred since `elements` method expects form has validator object that is created here
            _.defer(this.collectPristineValues.bind(this));
        },

        /**
         * Searches through backend rendered inputs which have errors, registers them and adds ID's to its error labels
         * to ability managing it in the same way as jquery.validate generated error labels
         */
        bindInitialErrors: function() {
            this.elementsOf(this.currentForm).each(function(i, element) {
                if (element.name && element.classList.contains('error')) {
                    var $label;
                    var classesSelector = this.settings.errorClass.split(' ').join('.');
                    var selector = this.settings.errorElement + '.' + classesSelector + ':not([id])';
                    var $placement = getErrorPlacement(element);

                    if ($placement.is('.fields-row-error')) {
                        $label = $placement.children(selector);
                    } else {
                        $label = $placement.nextAll(selector);
                    }

                    if ($label.length) {
                        var labelId = this.idOrName(element) + '-error';

                        if ($('#' + labelId).length === 0) {
                            $label.attr( 'id', labelId);
                            element.setAttribute('aria-describedby', labelId);
                        }
                    }

                    this.invalid[element.name] = true;
                }
            }.bind(this));
        },

        /**
         * Process server error response and shows messages on the form elements
         *
         * @param {Object} errors
         */
        showBackendErrors: function(errors) {
            var result = {};

            /**
             * Converts server error response:
             * {
             *   "children": {
             *     "message": {"errors": ["This value should not be blank."]},
             *     "attachment": {
             *       "children": {
             *         "file": {
             *           "errors": ["The file is too large (1146138 bytes). Allowed maximum size is 1048576 bytes."]
             *         }
             *       }
             *     }
             *   }
             * }
             *
             * to:
             * {
             *   "message": "This value should not be blank."
             *   "attachment[file]": "The file is too large (1146138 bytes). Allowed maximum size is 1048576 bytes."
             * }
             *
             * @param {Object} obj
             * @param {string=} path
             */
            (function parseBackendErrors(obj, path) {
                _.each(obj, function(item, name) {
                    var _path;
                    if (name === 'children') {
                        // skip 'children' level
                        parseBackendErrors(item, path);
                    } else {
                        _path = path ? (path + '[' + name + ']') : name;
                        if (_.isEqual(_.keys(item), ['errors']) && _.isArray(item.errors)) {
                            // only first error to show
                            result[_path] = item.errors[0];
                        } else if (_.isObject(item)) {
                            parseBackendErrors(item, _path);
                        }
                    }
                });
            })(errors);

            result = _.omit(result, function(message, name) {
                return !this.findByName(name)[0];
            }, this);

            if (!_.isEmpty(result)) {
                this.showErrors(result);
            }
        },

        collectPristineValues: function() {
            this.pristineValues = {};
            this.elementsOf(this.currentForm).each(_.bind(function(index, element) {
                if (!this.checkable(element) && element.name) {
                    this.pristineValues[element.name] = element.value;
                }
            }, this));
        },

        isPristine: function(element) {
            return this.pristineValues[element.name] === element.value;
        },

        /**
         * Resets form validation state
         *  - clears errors and validation history
         * (similar to validator.resetForm(), but does not change form elements' values)
         */
        resetFormErrors: function() {
            this.submitted = {};
            this.lastElement = null;
            this.prepareForm();
            this.hideErrors();
            this.elements().removeClass(this.settings.errorClass);
        },

        /**
         * Removes error message from an element
         *
         * @param {Element|jQuery} element
         */
        hideElementErrors: function(element) {
            var $placement = getErrorPlacement(element);
            var selector = '#' + this.idOrName(element) + '-error';

            if ($placement.is('.fields-row-error')) {
                $placement.children(selector).remove();
            } else {
                $placement.nextAll(selector).remove();
            }

            if (this.labelContainer.find(this.settings.errorClass.split(' ').join('.')).length === 0) {
                this.labelContainer.hide();
            }

            this.settings.unhighlight.call(this, element, this.settings.errorClass, this.settings.validClass);

            return this;
        },

        /**
         * Fetches descendant form elements which available for validation
         *
         * @param {Element|jQuery} element
         * @returns {jQuery}
         */
        elementsOf: function(element) {
            return $(element)
                .find('input, select, textarea')
                .not(':submit, :reset, :image, [disabled]:not([data-validate-element])')
                .not(this.settings.ignore);
        },

        hideThese: function(errors) {
            errors.not(this.containers).text('');
            this.addWrapper(errors).not(this.labelContainer).remove();

            if (this.labelContainer.find(this.settings.errorClass.split(' ').join('.')).length === 0) {
                this.labelContainer.hide();
            }
        },

        /**
         * Updates place for message label before show message
         *
         * @param {HTMLElement} element
         * @param {string} message
         */
        showLabel: function(element, message) {
            if (!message) {
                return;
            }

            var label = this.errorsFor(element);

            if (label.length) {
                this.settings.errorPlacement(label, element);
            }

            message = '<span><span>' + message + '</span></span>';

            original.showLabel.call(this, element, message);

            validateTopmostLabelMixin.showLabel.call(this, element, message, label);

            if (this.labelContainer.find(this.settings.errorClass.split(' ').join('.')).length) {
                this.labelContainer.show();
            }
        },

        defaultShowErrors: function() {
            original.defaultShowErrors.call(this);

            this.addWrapper(this.toShow).css('display', '');
        },

        /**
         * @inheritDoc
         */
        destroy: function() {
            // this.resetForm(); -- original reset form is to heavy,
            // it collects all the rules during collecting elements, we don't need it all
            if ($.fn.resetForm) {
                $(this.currentForm).resetForm();
            }
            this.invalid = {};
            this.submitted = {};
            this.prepareForm();
            this.hideErrors();

            $(this.currentForm)
                .off('.validate')
                .removeData('validator')
                .find('.validate-equalTo-blur')
                .off('.validate-equalTo')
                .removeClass('validate-equalTo-blur');

            validateTopmostLabelMixin.destroy.call(this);
        }
    });

    /**
     * Loader for custom validation methods
     *
     * @param {string|Array.<string>} module name of AMD module or list of modules
     */
    $.validator.loadMethod = function(module) {
        tools.loadModules($.makeArray(module), function() {
            _.each(arguments, function(args) {
                $.validator.addMethod.apply($.validator, args);
            });
        });
    };

    $.validator.setDefaults({
        errorElement: 'span',
        errorClass: 'validation-failed',
        errorPlacement: function(label, $el) {
            var $placement = getErrorPlacement($el);
            // we need this to remove server side error, because js does not know about it
            if ($placement.is('.fields-row-error')) {
                label.appendTo($placement);
            } else {
                label.insertAfter($placement);
            }
        },
        highlight: function(element) {
            var $el = getErrorTarget(element);
            $el.addClass('error')
                .closest('.controls').addClass('validation-error');
            $el.closest('.control-group').find('.control-label').addClass('validation-error');
        },
        unhighlight: function(element) {
            var $el = getErrorTarget(element);
            $el.removeClass('error')
                .closest('.controls')
                .removeClass('validation-error');
            $el.closest('.control-group').find('.control-label').removeClass('validation-error');
        },
        // ignore all invisible elements except input type=hidden, which are ':input[data-validate-element]'
        ignore: ':hidden:not([type=hidden]), [data-validation-ignore] :input',
        onfocusout: function(element) {
            if (!$(element).is(':disabled') && !this.checkable(element) && !this.isPristine(element)) {
                if ($(element).hasClass('select2-focusser')) {
                    var $selectContainer = $(element).closest('.select2-container');

                    // prevent validation if selection still in progress
                    if ($selectContainer.hasClass('select2-dropdown-open')) {
                        return;
                    }

                    var realField = $selectContainer.parent()
                        .find('.select2[type=hidden], select.select2')[0];

                    if (realField) {
                        if (!this.isPristine(realField) || realField.name in this.submitted) {
                            this.element(realField);
                        }

                        return;
                    }
                }

                this.element(element);
            }
        },
        onkeyup: function(element, event) {
            if (element.name in this.submitted || element.name in this.invalid) {
                this.element(element);
            }
        }
    });

    // general validation methods
    var methods = [
        'oroform/js/validator/count',
        'oroform/js/validator/date',
        'oroform/js/validator/datetime',
        'oroform/js/validator/email',
        'oroform/js/validator/length',
        'oroform/js/validator/notblank',
        'oroform/js/validator/notnull',
        'oroform/js/validator/number',
        'oroform/js/validator/range',
        'oroform/js/validator/open-range',
        'oroform/js/validator/regex',
        'oroform/js/validator/repeated',
        'oroform/js/validator/time',
        'oroform/js/validator/url',
        'oroform/js/validator/type',
        'oroform/js/validator/callback'
    ];
    $.validator.loadMethod(methods);

    /**
     * Extend original dataRules method and implements
     *
     * - optional-group validation:
     *     if all fields of optional-group container have empty value - validation is turned off
     * - no validation group:
     *     if the element inside of the container with turned off validation - no validation rules
     *
     * @type {Function}
     */
    $.validator.dataRules = _.wrap($.validator.dataRules, function(dataRules, element) {
        var optionalGroup;
        var validator;
        var rules = dataRules(element);
        if (!$.isEmptyObject(rules)) {
            optionalGroup = $(element).parents('[data-validation-optional-group]').get(0);
        }
        if (optionalGroup) {
            validator = $(element.form).data('validator');
            validator.settings.unhighlight(element);
            _.each(rules, function(param) {
                param.depends = function() {
                    // all fields in a group failed a required rule (have empty value) - stop group validation
                    var isValidFound = false;
                    var isInvalidFound = false;
                    _.each(validator.elementsOf(optionalGroup), function(elem) {
                        var $element = $(elem);
                        if ($element.prop('willValidate') && !$element.data('ignore-validation')) {
                            if ($.validator.methods.required.call(validator, validator.elementValue(elem), elem)) {
                                isValidFound = true;
                            } else {
                                isInvalidFound = true;
                            }
                        }
                    });

                    return isValidFound && isInvalidFound;
                };
            });
        }
        return rules;
    });

    /**
     * Extend original addMethod method and implements
     *
     * - validation methods:
     *     method can resolve array of params in same validation method
     *
     * @type {Function}
     */
    $.validator.addMethod = _.wrap($.validator.addMethod, function(addMethod, name, method, message) {
        method = _.wrap(method, function(method, value, element, params) {
            if (!_.isArray(params)) {
                return method.call(this, value, element, params);
            }
            return _.every(params, function(param, index) {
                var result = method.call(this, value, element, param);
                if (!result) {
                    params.failedIndex = index;
                }
                return result;
            }, this);
        });

        if (_.isFunction(message)) {
            message = _.wrap(message, function(message, params, element) {
                if (!_.isArray(params)) {
                    return message.call(this, params, element);
                }
                var param = params[params.failedIndex];
                delete params.failedIndex;
                if (param === undefined) {
                    var e = new Error(
                        'For multi-rule validations you should call rule "method" function before access to message.'
                    );
                    error.showErrorInConsole(e);
                    throw e;
                }
                return message.call(this, param, element);
            });
        } else if (_.isString(message)) {
            var translated = __(message);
            message = function() {
                return translated;
            };
        }

        return addMethod.call(this, name, method, message);
    });

    /**
     * Filters unsupported validation rules from validator configuration object
     *
     * @param {Object} validationRules - validator configuration object
     * @returns {Object} filtered validation rules
     */
    $.validator.filterUnsupportedValidators = function(validationRules) {
        var validationRulesCopy = $.extend(true, {}, validationRules);
        for (var ruleName in validationRulesCopy) {
            if (validationRulesCopy.hasOwnProperty(ruleName)) {
                if (!_.isFunction($.validator.methods[ruleName])) {
                    logger.warn('Cannot find validator implementation for `{{rule}}`', {rule: ruleName});
                    delete validationRulesCopy[ruleName];
                }
            }
        }
        return validationRulesCopy;
    };

    return $;
});
