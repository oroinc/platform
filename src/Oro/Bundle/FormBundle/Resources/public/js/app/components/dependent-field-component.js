import $ from 'jquery';
import _ from 'underscore';
import BaseComponent from 'oroui/js/app/components/base/component';

const VISIBILITY_CHANGE_EVENT = 'dependent-field:visibility-change';
const HIDDEN_DATA_KEY = 'dependentFieldHidden';

/**
 * Allows to show/hide dependent element depending on the condition which is based on the value of dependee form field.
 *
 * Dependee form field configuration:
 *  - "data-dependee-id" attribute is required and should be filled with ID of dependee so you can rely on it
 *  in "data-depend-on" of dependent element.
 *
 * Dependent element configuration:
 *  - "data-depend-on" attribute is required and should be filled with ID of dependee, which you specified in its
 * "data-dependee-id" attribute.
 *
 *  - "data-show-if" attribute is optional and should be filled with condition which represents the list of
 *  value(s) of dependee, which are required to show the dependent element. List of values must be divided with "&"
 *  (conjunction operator) or "|" (disjunction operator) - it will be evaluated according to common logic expression
 *  rules. Examples:
 *  1) data-show-if="pre_configured" - dependent element will be shown if dependee value is "pre_configured"
 *  2) data-show-if="pre_configured | allow_user" - dependent element will be shown if dependee value is
 *  "pre_configured" or "allow_user" - in case if multiple choices are allowed.
 *  3) data-show-if="pre_configured | allow_user & custom" - dependent element will be shown if dependee value is
 *  "pre_configured" or ("allow_user" and "custom") - in case if multiple choices are allowed.
 *  4) data-show-if="checked" - dependent element will be shown if dependee is checked - in case of checkbox.
 *  Use "unchecked" for the opposite condition.
 *  5) data-disable-element="input" - element in the dependent element will be disabled if the dependee is hiding
 *
 *  - "data-hide-if" attribute is optional and should be filled with condition which represents the list of
 *  value(s) of dependee, which are required to hide the dependent element. This option has higher priority
 *  than showing option, so if both "data-show-if" and "data-hide-if" are true, than the dependent element will be
 *  hidden.
 *
 *  - "data-clear-if" attribute is optional and should be filled with condition which represents the list of
 *  value(s) of dependee, which are required to clear the dependent element. List of values must be divided with "&"
 *  (conjunction operator) or "|" (disjunction operator) - it will be evaluated according to common logic expression
 *  rules.
 * - "data-clear-element" attribute is optional and should be used if it is needed to clear some form elements
 *  in dependee element.
 *
 *  - "data-disable-element" - attribute is optional and should be used if need to additionally to disable
 *  some form elements in dependee element.
 *
 * A dependee can itself be a dependent element, forming a dependency chain (e.g. C depends on B, which depends on A).
 * In this case, the dependent element (C) becomes hidden when (A) hides (B), regardless of the current value of (B).
 */
const DependentFieldComponent = BaseComponent.extend({
    /**
     * @property {Object}
     */
    options: {
        selectors: {
            rowContainer: '.control-group'
        },
        groupContainerSelector: '.control-group-wrapper',
        dependentContainerClass: 'dependent-fields-container',
        rowClassName: 'dependent-field'
    },

    /**
     * @property {jQuery}
     */
    $form: null,

    /**
     * @property {jQuery}
     */
    $el: null,

    /**
     * @property {jQuery}
     */
    $dependee: null,

    /**
     * @inheritdoc
     */
    constructor: function DependentFieldComponent(options) {
        DependentFieldComponent.__super__.constructor.call(this, options);
    },

    /**
     * @param {Object} options
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);

        this.$el = this.options._sourceElement;
        this.$form = this.$el.closest('form');
        this.$dependee = this.$form.find('[data-dependee-id="' + this.$el.data('dependOn') + '"]');

        if (this.$dependee.length) {
            this.updateDependentFields = this.updateDependentFields.bind(this);
            this.updateDependentFields(null, true);
            this.$dependee.on(`change ${VISIBILITY_CHANGE_EVENT}`, this.updateDependentFields);
        }

        this.$el.closest(this.options.groupContainerSelector).addClass(this.options.dependentContainerClass);
        this.$el.closest(this.options.selectors.rowContainer).addClass(this.options.rowClassName);

        this.$el.inputWidget('create');
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.$el.closest(this.options.groupContainerSelector).removeClass(this.options.dependentContainerClass);
        this.$el.closest(this.options.selectors.rowContainer).removeClass(this.options.rowClassName);
        if (this.$dependee) {
            this.$dependee.off(`change ${VISIBILITY_CHANGE_EVENT}`, this.updateDependentFields);
        }

        DependentFieldComponent.__super__.dispose.call(this);
    },

    /**
     * @returns {Array}
     */
    getDependeeValue: function() {
        let value = [];

        if (this.$dependee.is(':checkbox')) {
            value = this.$dependee.prop('checked') ? 'checked' : 'unchecked';
        } else {
            value = this.$dependee.val();
        }

        if (!(value instanceof Array)) {
            value = [value];
        }

        return value;
    },

    /**
     * @returns {string}
     */
    getShowIf: function() {
        return this.$el.data('showIf');
    },

    /**
     * @returns {string}
     */
    getHideIf: function() {
        return this.$el.data('hideIf');
    },

    /**
     * @returns {string}
     */
    getClearIf: function() {
        return this.$el.data('clearIf');
    },

    /**
     * @returns {string}
     */
    getClearElement: function() {
        return this.$el.data('clearElement') || ':input';
    },

    /**
     * @returns {string}
     */
    getDisableElement: function() {
        return this.$el.data('disableElement');
    },

    /**
     * Evaluate condition
     *
     * @returns {boolean}
     */
    evaluateCondition: function() {
        if (this.$dependee.data(HIDDEN_DATA_KEY)) {
            return false;
        }

        const showIf = this.getShowIf();
        const hideIf = this.getHideIf();
        let condition = typeof showIf !== 'undefined' ? this.evaluateDisjunction(showIf) : true;

        // Evaluate hide condition only if it is specified.
        if (typeof hideIf !== 'undefined') {
            condition = condition && !this.evaluateDisjunction(this.getHideIf());
        }

        return condition;
    },

    evaluateClearCondition() {
        const clearIf = this.getClearIf();

        return typeof clearIf !== 'undefined' ? this.evaluateDisjunction(clearIf) : false;
    },

    /**
     * @param {string} condition
     *
     * @returns {boolean}
     */
    evaluateConjunction: function(condition) {
        const conditionsCol = typeof condition === 'string' ? condition.split(/\s?\&\s?/) : [];
        const value = this.getDependeeValue();

        for (const a in conditionsCol) {
            if (!conditionsCol.hasOwnProperty(a)) {
                continue;
            }

            if (value.indexOf(conditionsCol[a]) === -1) {
                return false;
            }
        }

        return true;
    },

    /**
     * @param {string} condition
     *
     * @returns {boolean}
     */
    evaluateDisjunction: function(condition) {
        const conditionsCol = typeof condition === 'string' ? condition.split(/\s?\|\s?/) : [];
        const value = this.getDependeeValue();
        let result;

        for (const a in conditionsCol) {
            if (!conditionsCol.hasOwnProperty(a)) {
                continue;
            }

            if (conditionsCol[a].indexOf('&') !== -1) {
                result = this.evaluateConjunction(conditionsCol[a]);
            } else {
                result = value.indexOf(conditionsCol[a]) !== -1;
            }

            if (result === true) {
                return true;
            }
        }

        return false;
    },

    /**
     * Updates dependent field based on condition.
     *
     * @param {jQuery.Event} [e]
     * @param {boolean} [isInit]
     */
    updateDependentFields: function(e, isInit) {
        const condition = this.evaluateCondition();
        this.$el
            .closest(this.options.selectors.rowContainer)
            .toggleClass('hide', !condition);
        this.setDisableState(!condition);
        // propagate further down the dependency chain
        this.$el.data(HIDDEN_DATA_KEY, !condition).trigger(VISIBILITY_CHANGE_EVENT);

        if (!isInit && e.type === 'change') {
            this.clearOrRestoreElement(this.evaluateClearCondition());
        }
    },

    clearOrRestoreElement(doClear) {
        const clearElementSelector = this.getClearElement();
        if (clearElementSelector) {
            this.$el
                .closest(this.options.selectors.rowContainer)
                .find(clearElementSelector).each((index, element) => {
                    const $el = $(element);

                    if (doClear) {
                        let value;
                        if ($el.is(':checkbox')) {
                            value = $el.prop('checked');
                            $el.prop('checked', false);
                        } else {
                            value = $el.val();
                            $el.val('');
                        }

                        $el.data('clearedValue', value);
                    } else {
                        const previousValue = $el.data('clearedValue');
                        if (previousValue !== undefined) {
                            if ($el.is(':checkbox')) {
                                $el.prop('checked', previousValue);
                            } else {
                                $el.val(previousValue);
                            }

                            $el.removeData('clearedValue');
                        }
                    }
                });
        }
    },

    /**
     * @param {boolean} flag
     */
    setDisableState: function(flag) {
        const disableElementSelector = this.getDisableElement();
        if (disableElementSelector) {
            this.$el
                .closest(this.options.selectors.rowContainer)
                .find(disableElementSelector)
                .prop('disabled', flag);
        }
    }
});

export default DependentFieldComponent;
