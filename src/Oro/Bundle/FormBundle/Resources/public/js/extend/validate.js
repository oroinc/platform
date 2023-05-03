define(function(require, exports, module) {
    'use strict';

    const $ = require('jquery.validate');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const loadModules = require('oroui/js/app/services/load-modules');
    const logger = require('oroui/js/tools/logger');
    const validationHandler = require('oroform/js/optional-validation-groups-handler');
    const error = require('oroui/js/error');
    const config = require('module-config').default(module.id);
    const validateTopmostLabelMixin = config.useTopmostLabelMixin
        ? require('oroform/js/validate-topmost-label-mixin') : null;
    const messageTemplate = require('tpl-loader!oroform/templates/error-template.html');

    const original = _.pick($.validator.prototype, 'init', 'showLabel', 'defaultShowErrors', 'resetElements');

    const ERROR_CLASS_NAME = 'error';

    /**
     * Collects all ancestor elements that have validation rules
     *
     * @param {Element|jQuery} element
     * @returns {Array.<Element>} sorted in order from form element to input element
     */
    function validationHolders(element) {
        let elems = [];
        const $el = $(element);
        const form = $el.parents('form').first();
        // instance of validator
        const validator = $(form).data('validator');
        if (validator instanceof $.validator) {
            elems = _.filter($el.add($el.parentsUntil(form)).add(form).toArray(), function(el) {
                const $el = $(el);
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
        const validations = _.map(validationHolders(element), function(el) {
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
        let $target = $(validationBelongs(element));
        const $widgetContainer = $target.inputWidget('getContainer');
        if ($widgetContainer) {
            $target = $widgetContainer;
        }
        const $parent = $target.parent();
        if ($parent.is('.input-append, .input-prepend')) {
            $target = $parent;
        }
        let $validateGroup;
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
        const $targetElem = getErrorTarget(element);
        const $errorHolder = $targetElem.closest('.fields-row');

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
        const $el = $(el);
        const validation = $el.data('validation');

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
        let rules = {};
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
                const _rules = {};
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

        const isValid = check.call(this, element);

        if (validateTopmostLabelMixin && isValid) {
            validateTopmostLabelMixin.validationSuccessHandler.call(this, element);
        }

        return isValid;
    });

    $.validator.prototype.valid = _.wrap($.validator.prototype.valid, function(valid) {
        const isValid = valid.call(this);
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
        const $additionalElements = $(this.currentForm).find(':input[data-validate-element]');
        return func.call(this).add($additionalElements);
    });

    // saves name of validation rule which is violated
    $.validator.prototype.formatAndAdd = _.wrap($.validator.prototype.formatAndAdd, function(func, element, rule) {
        $(element).data('violated', rule.method);
        return func.call(this, element, rule);
    });

    $.validator.prototype.destroy = _.wrap($.validator.prototype.destroy, function(originDestroy) {
        if (validateTopmostLabelMixin) {
            validateTopmostLabelMixin.destroy.call(this);
        }

        originDestroy.call(this);
    });

    // fixes focus on select2 element and problem with focus on hidden inputs
    $.validator.prototype.focusInvalid = _.wrap($.validator.prototype.focusInvalid, function(func) {
        if (!this.settings.focusInvalid) {
            return func.call(this);
        }

        const $elem = $(this.findLastActive() || (this.errorList.length && this.errorList[0].element) || []);
        const $firstValidationError = $('.validation-failed').filter(':visible').first();

        if ($elem.is('.select2[type=hidden]') || $elem.is('select.select2')) {
            $elem.parent().find('input.select2-focusser')
                .focus()
                .trigger('focusin');
        } else if (!$elem.filter(':visible').length && $firstValidationError.length) {
            const $scrollableContainer = $firstValidationError.closest('.scrollable-container');
            const scrollTop = $firstValidationError.position().top + $scrollableContainer.scrollTop();

            $scrollableContainer.animate({
                scrollTop: scrollTop
            }, scrollTop / 2);
        } else {
            return func.call(this);
        }
    });

    $.validator.prototype.resetForm = _.wrap($.validator.prototype.resetForm, function(resetForm) {
        resetForm.call(this);
        this.collectPristineValues();
    });

    _.extend($.validator.prototype, {
        init: function() {
            validationHandler.initialize($(this.currentForm));

            if (validateTopmostLabelMixin) {
                validateTopmostLabelMixin.init.call(this);
            }

            $(this.currentForm).on({
                'content:initialized.validate': function(e) {
                    this.bindInitialErrors(e.target);
                }.bind(this),
                'content:changed.validate': function(event) {
                    validationHandler.initializeOptionalValidationGroupHandlers($(event.target));
                },
                'disabled.validate': function(e) {
                    this.hideElementErrors(e.target);
                }.bind(this)
            });

            $.validator.preloadMethods()
                .then(this.settings.onMethodsLoaded || (() => {}));

            original.init.call(this);

            this.bindInitialErrors();

            // Following call is deferred since `elements` method expects form has validator object that is created here
            _.defer(this.collectPristineValues.bind(this));
        },

        /**
         * Searches through backend rendered inputs which have errors, registers them and adds ID's to its error labels
         * to ability managing it in the same way as jquery.validate generated error labels
         */
        bindInitialErrors: function(container) {
            this.elementsOf(container || this.currentForm).each(function(i, element) {
                if (element.name && element.classList.contains(ERROR_CLASS_NAME)) {
                    let $label;
                    const classesSelector = this.settings.errorClass.split(' ').join('.');
                    const selector = this.settings.errorElement + '.' + classesSelector + ':not([id])';
                    const $placement = getErrorPlacement(element);

                    if ($placement.is('.fields-row-error')) {
                        $label = $placement.children(selector);
                    } else {
                        $label = $(element).nextAll(selector);
                    }

                    element.classList.remove(ERROR_CLASS_NAME);
                    this.settings.highlight(element);

                    if ($label.length) {
                        const text = [];

                        $label.each(function() {
                            text.push(_.escape($(this).text()));
                        });
                        this.showLabel(element, text.join('<br>'));
                        $label.remove();
                    }

                    this.invalid[element.name] = true;
                }
            }.bind(this));
        },

        /**
         * Process server error response and shows messages on the form elements
         *
         * @param {Object} errors
         * @param {string?} namePrefix
         */
        showBackendErrors: function(errors, namePrefix) {
            let result = {};

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
                    let _path;
                    if (name === 'children') {
                        // skip 'children' level
                        parseBackendErrors(item, path);
                    } else {
                        _path = path ? `${path}[${name}]` : namePrefix ? `${namePrefix}[${name}]` : name;
                        if ('errors' in item && _.isArray(item.errors)) {
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

            _.each(_.keys(result), function(name) {
                this.invalid[name] = true;
            }, this);

            if (!_.isEmpty(result)) {
                this.showErrors(result);
            }
        },

        collectPristineValues: function() {
            this.pristineValues = {};
            this.elementsOf(this.currentForm).each((index, element) => {
                if (!this.checkable(element) && element.name) {
                    this.pristineValues[element.name] = element.value;
                }
            });
        },

        isPristine: function(element) {
            if (this.pristineValues === void 0) {
                return false;
            }
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

        resetElements: function(elements) {
            original.resetElements.call(this, elements);

            if (validateTopmostLabelMixin) {
                _.forEach(elements, validateTopmostLabelMixin.validationResetHandler.bind(this));
            }
        },

        /**
         * Removes error message from an element
         *
         * @param {Element|jQuery} element
         */
        hideElementErrors: function(element) {
            const $placement = getErrorPlacement(element);
            // Since name of input can contain `[]` lets use `[id=...` selector instead `#...` to avoid jQuery error
            const selector = '[id="' + this.idOrName($(element)[0]) + '-error"]';

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
            if (!message || !element) {
                return;
            }

            const label = this.errorsFor(element);

            if (label.length) {
                this.settings.errorPlacement(label, element);
            }

            message = this.settings.errorMessageTemplate({message: message});

            original.showLabel.call(this, element, message);

            if (validateTopmostLabelMixin) {
                validateTopmostLabelMixin.showLabel.call(this, element, message, label);
            }

            if (this.labelContainer.find(this.settings.errorClass.split(' ').join('.')).length) {
                this.labelContainer.show();
            }
        },

        defaultShowErrors: function() {
            original.defaultShowErrors.call(this);

            this.addWrapper(this.toShow).css('display', '');

            const updateListElement = ($elements, invalid) => {
                $elements.each((index, el) => {
                    $(el)
                        .attr('aria-invalid', invalid)
                        .trigger({
                            type: 'validate-element',
                            errorClass: ERROR_CLASS_NAME,
                            invalid
                        });
                });
            };

            if (this.errorList.length) {
                this.errorList
                    .forEach(item => {
                        let $elements = $(`[name="${item.element.getAttribute('name')}"]`);

                        if (!$elements.length) {
                            $elements = $(item.element);
                        }

                        updateListElement($elements, true);
                    });
            }

            if (this.toHide.length) {
                this.toHide.each((i, el) => {
                    let id = el.getAttribute('id');

                    if (!id) {
                        return;
                    }

                    // find element by original ID without "-error" postfix
                    id = id.slice(0, -6);

                    let $elements = $(`[name="${id}"]`);

                    if (!$elements.length) {
                        $elements = $(document.getElementById(id));
                    }

                    updateListElement($elements, false);
                });
            }
        },

        /**
         * @inheritdoc
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

            if (validateTopmostLabelMixin) {
                validateTopmostLabelMixin.destroy.call(this);
            }
        }
    });

    /**
     * Registers modules to load with custom validation methods
     *
     * @param {string|Array.<string>} module name or list of modules to load
     */
    $.validator.loadMethod = function(module) {
        const {_methodsToLoad: modules = []} = $.validator;
        modules.push(...$.makeArray(module));
        $.validator._methodsToLoad = modules;
    };

    /**
     * Loads registered for custom validation methods
     */
    $.validator._loadMethod = function() {
        const {_methodsToLoad: modules = []} = $.validator;
        $.validator._methodsToLoad = []; // flush collected modules
        return loadModules(modules, (...methods) => {
            methods.forEach(method => $.validator.addMethod(...method));
        }).then(() => {
            if ($.validator._methodsToLoad.length) {
                // there are new methods were added to load
                return $.validator._loadMethod();
            }
        });
    };

    $.validator._loadMethodPromises = [];

    /**
     * Allows to preload validation methods before validator initialization
     * @return {Promise}
     */
    $.validator.preloadMethods = function() {
        $.validator._loadMethodPromises.push($.validator._loadMethod());
        return Promise.all($.validator._loadMethodPromises);
    };

    $.validator.setDefaults({
        errorElement: 'span',
        errorClass: 'validation-failed',
        errorElementClassName: ERROR_CLASS_NAME,
        errorMessageTemplate: messageTemplate,
        errorPlacement: function(label, $el) {
            const $placement = getErrorPlacement($el);
            // we need this to remove server side error, because js does not know about it
            if ($placement.is('.fields-row-error')) {
                label.appendTo($placement);
            } else {
                label.insertAfter($placement);
            }
        },
        highlight: function(element) {
            const $el = getErrorTarget(element);

            if (element !== $el[0]) {
                $(element).addClass(ERROR_CLASS_NAME);
            }

            $el.addClass(ERROR_CLASS_NAME)
                .closest('.controls').addClass('validation-error');
            $el.closest('.control-group').find('.control-label').addClass('validation-error');
        },
        unhighlight: function(element) {
            const $target = getErrorTarget(element);
            let validGroup = true;

            // Check if present more than one element in related container
            if (!$target.is(':input')) {
                const groupElementNames = _.reduce(this.elementsOf($target), function(memo, num) {
                    memo.push(num.name);
                    return memo;
                }, []);

                for (let i = 0; groupElementNames.length > i; i++) {
                    if (this.invalid[groupElementNames[i]] === true) {
                        validGroup = false;
                        break;
                    }
                }
            }

            if (validGroup) {
                if (element !== $target[0]) {
                    $(element).removeClass(ERROR_CLASS_NAME);
                }

                $target.removeClass(ERROR_CLASS_NAME)
                    .closest('.controls')
                    .removeClass('validation-error');
                $target.closest('.control-group').find('.control-label').removeClass('validation-error');
            }
        },
        // ignore all invisible elements except input type=hidden, which are ':input[data-validate-element]'
        ignore: [
            ':hidden:not([type=hidden], [data-validation-force], [data-validation-force] :input)',
            '[data-validation-ignore] :input'
        ].join(', '),
        onfocusout: function(element) {
            if (
                !$(element).is(':disabled, [data-validation-ignore-onblur]') &&
                !this.checkable(element) &&
                !this.isPristine(element)
            ) {
                if ($(element).hasClass('select2-focusser')) {
                    const $selectContainer = $(element).closest('.select2-container');

                    // if this is a compound field, check parent container for ignore data attribute
                    if ($selectContainer.parents('[data-validation-ignore-onblur]').length) {
                        return;
                    }

                    // prevent validation if selection still in progress
                    if ($selectContainer.hasClass('select2-dropdown-open')) {
                        return;
                    }

                    const realField = $selectContainer.parent()
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
        let optionalGroup;
        const rules = dataRules(element);
        if (!$.isEmptyObject(rules)) {
            optionalGroup = $(element).parents('[data-validation-optional-group]').get(0);
        }
        if (optionalGroup) {
            const validator = $(element.form).data('validator');
            validator.settings.unhighlight.call(validator, element);
            _.each(rules, function(param) {
                param.depends = function() {
                    // all fields in a group failed a required rule (have empty value) - stop group validation

                    return _.some(validator.elementsOf(optionalGroup), function(element) {
                        const $element = $(element);

                        return $element.prop('willValidate') && !$element.data('ignore-validation') &&
                            $.validator.methods.required.call(validator, validator.elementValue(element), element);
                    });
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
                const result = method.call(this, value, element, param);
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
                const param = params[params.failedIndex];
                delete params.failedIndex;
                if (param === undefined) {
                    const e = new Error(
                        'For multi-rule validations you should call rule "method" function before access to message.'
                    );
                    error.showErrorInConsole(e);
                    throw e;
                }
                return message.call(this, param, element);
            });
        } else if (_.isString(message)) {
            const translated = __(message);
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
        const validationRulesCopy = $.extend(true, {}, validationRules);
        for (const ruleName in validationRulesCopy) {
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
