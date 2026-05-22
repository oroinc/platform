import $ from 'jquery';
import _ from 'underscore';
import pageStateChecker from 'oronavigation/js/app/services/page-state-checker';
import BaseView from 'oroui/js/app/views/base/view';

const IGNORE = [':input[type=button]', ':input[type=submit]', ':input[type=reset]',
    ':input[type=password]', ':input[type=file]', ':input[name$="[_token]"]',
    '[data-ignore-form-state-change] :input', ':input[name^=temp-validation-name]',
    ':input[data-fake-front-field]'].join(', ');

/**
 * Reusable view that tracks form state and detects unsaved changes.
 * Captures an initial snapshot of form inputs and compares it to the current state
 * to determine whether the form has been modified.
 *
 * Registers itself in the global pageStateChecker service so that other components
 * (e.g., page navigation, datagrid plugins) can query for unsaved changes.
 *
 * @export oroform/js/app/views/form-state-tracker-view
 * @class oroform.app.views.FormStateTrackerView
 * @extends oroui.app.views.base.View
 */
const FormStateTrackerView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'autoCapture',
        'additionalIgnore',
        'ignore',
        'group'
    ]),

    ignore: IGNORE,

    autoCapture: false,

    additionalIgnore: '',

    group: 'default',

    initialState: null,

    events: {
        patchInitialState: 'onPatchInitialState'
    },

    constructor: function FormStateTrackerView(...args) {
        this.hasChanges = this.hasChanges.bind(this);
        FormStateTrackerView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.ignoreSelector = this.additionalIgnore
            ? this.ignore + ', ' + this.additionalIgnore
            : this.ignore;

        FormStateTrackerView.__super__.initialize.call(this, options);

        pageStateChecker.registerChecker(this.hasChanges);

        const registry = FormStateTrackerView.registry;

        if (!registry[this.group]) {
            registry[this.group] = [];
        }

        registry[this.group].push(this);

        if (this.autoCapture) {
            this.captureInitialState();
        }
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        pageStateChecker.removeChecker(this.hasChanges);

        const list = FormStateTrackerView.registry[this.group];

        if (list) {
            const index = list.indexOf(this);

            if (index !== -1) {
                list.splice(index, 1);
            }

            if (!list.length) {
                delete FormStateTrackerView.registry[this.group];
            }
        }

        FormStateTrackerView.__super__.dispose.call(this);
    },

    onPatchInitialState(event) {
        this.patchInitialState($(event.target));
    },

    /**
     * Captures the current form state as the baseline for future comparisons.
     */
    captureInitialState() {
        this.initialState = this.collectFormData();
    },

    /**
     * Resets the initial state so that hasChanges() returns false.
     */
    resetInitialState() {
        this.initialState = null;
    },

    /**
     * Returns true if the current form state differs from the captured initial state.
     *
     * @returns {boolean}
     */
    hasChanges() {
        if (this.disposed || this.initialState === null) {
            return false;
        }

        const currentState = this.collectFormData();

        return this.isDifferentFromInitialState(currentState);
    },

    /**
     * Updates part of the initial state with data from the given element.
     * Replaces matching fields by name sequence, or inserts new fields
     * at the correct position relative to neighbors.
     *
     * @param {jQuery} $elem - an input element or a container with inputs
     */
    patchInitialState($elem) {
        if (this.initialState === null) {
            return;
        }

        const patchData = this.extractInputsData($elem);

        if (!patchData.length) {
            return;
        }

        const findIndexInState = (state, patch) => {
            return state.findIndex((unused, index) => {
                return patch.every((patchItem, patchIndex) => {
                    return state[index + patchIndex] &&
                        state[index + patchIndex].name === patchItem.name;
                });
            });
        };

        const replaceIndex = findIndexInState(this.initialState, patchData);

        if (replaceIndex !== -1) {
            this.initialState.splice(replaceIndex, patchData.length, ...patchData);
            return;
        }

        const fullState = this.extractInputsData(this.$el);
        const newIndex = findIndexInState(fullState, patchData);

        if (newIndex === -1) {
            return;
        }

        const prevItem = fullState[newIndex - 1];
        const nextItem = fullState[newIndex + patchData.length];

        if (prevItem) {
            const index = _.findLastIndex(this.initialState, item => item.name === prevItem.name);
            if (index !== -1 && (!nextItem || (this.initialState[index + 1] &&
                this.initialState[index + 1].name === nextItem.name))) {
                this.initialState.splice(index + 1, 0, ...patchData);
            }
        } else if (nextItem) {
            const index = this.initialState.findIndex(item => item.name === nextItem.name);
            if (index !== -1) {
                this.initialState.splice(index, 0, ...patchData);
            }
        }
    },

    /**
     * Serializes all tracked inputs within the view element.
     *
     * @returns {Array<{name: string, value: string}>}
     */
    collectFormData() {
        return this.extractInputsData(this.$el);
    },

    /**
     * Extracts name-value data from inputs within the given element.
     *
     * @param {jQuery} $elem - input element or container
     * @returns {Array<{name: string, value: string}>}
     */
    extractInputsData($elem) {
        const inputSelector = `:input:not(${this.ignoreSelector})`;
        const $inputs = $elem.is(inputSelector) ? $elem : $elem.find(inputSelector);

        return $inputs
            .toArray()
            .map(input => {
                const $input = $(input);

                if (!$input.is('.select2[type=hidden]')) {
                    return $input.serializeArray()[0];
                }

                return {name: input.name, value: $input.val()};
            })
            .filter(item => item);
    },

    /**
     * Compares current state against the initial state by name-value pairs.
     *
     * @param {Array<{name: string, value: string}>} currentState
     * @returns {boolean}
     */
    isDifferentFromInitialState(currentState) {
        const initial = this.initialState;

        if (!initial) {
            return false;
        }

        const filtered = currentState.filter(
            field => _.isObject(field) && field.name.indexOf('temp-validation-name-') === -1
        );

        const filteredInitial = initial.filter(
            field => _.isObject(field) && field.name.indexOf('temp-validation-name-') === -1
        );

        if (filtered.length !== filteredInitial.length) {
            return true;
        }

        return !_.every(filteredInitial, (field, j) => {
            return _.isObject(filtered[j]) &&
                filtered[j].name === field.name &&
                filtered[j].value === field.value;
        });
    }
}, {
    registry: {},

    ignoredGroups: {},

    hasChangesInGroup(group) {
        if (FormStateTrackerView.ignoredGroups[group]) {
            return false;
        }

        const trackers = FormStateTrackerView.registry[group];

        if (!trackers) {
            return false;
        }

        return trackers.some(tracker => !tracker.disposed && tracker.hasChanges());
    },

    ignoreChangesInGroup(group) {
        FormStateTrackerView.ignoredGroups[group] = true;
    },

    resetIgnoredChangesInGroup(group) {
        delete FormStateTrackerView.ignoredGroups[group];
    }
});

export default FormStateTrackerView;
