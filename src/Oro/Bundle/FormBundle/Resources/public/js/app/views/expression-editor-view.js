import $ from 'jquery';
import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import 'bootstrap-typeahead';

const Typeahead = $.fn.typeahead.Constructor;

const ExpressionEditorView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'dataSource', 'delay'
    ]),

    /**
     * @type {ExpressionEditorUtil}
     */
    util: null,

    /**
     * {Object} Typeahead
     */
    typeahead: null,

    /**
     * @type {AutocompleteData} Autocomplete data provided by ExpressionEditorUtil.getAutocompleteData
     */
    autocompleteData: null,

    /**
     * {Object} List of data source widgets
     */
    dataSource: null,

    /**
     * {Object} List of data source widget instances
     */
    dataSourceInstances: null,

    /**
     * @type {number} Validation and autocomplete delay in milliseconds
     */
    delay: 50,

    events: {
        focus: 'debouncedAutocomplete',
        click: 'debouncedAutocomplete',
        input: 'debouncedAutocomplete',
        blur: 'debouncedOnChange',
        keyup: 'debouncedOnChange',
        paste: 'debouncedOnChange',
        change: 'debouncedOnChange'
    },

    /**
     * @inheritdoc
     */
    constructor: function ExpressionEditorView(options) {
        this.debouncedAutocomplete = _.debounce(e => {
            if (!this.disposed) {
                this.autocomplete(e);
            }
        }, this.delay);
        this.debouncedOnChange = _.debounce(e => {
            if (!this.disposed) {
                this.onChange(e);
            }
        }, this.delay);
        ExpressionEditorView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        if (!options.util) {
            throw new Error('Option `util` is required for `ExpressionEditorView`');
        }

        _.extend(this, _.pick(options, 'util'));

        this.autocompleteData = {};
        this.dataSource = this.dataSource || {};
        this.dataSourceInstances = {};

        if (_.isRTL()) {
            this.$el.attr('dir', 'ltr');
        }

        return ExpressionEditorView.__super__.initialize.call(this, options);
    },

    render() {
        this._toggleErrorState(this.isValid());
        this.$el.typeahead({
            minLength: 0,
            items: 20,
            select: this._typeaheadSelect.bind(this),
            source: this._typeaheadSource.bind(this),
            lookup: this._typeaheadLookup.bind(this),
            highlighter: this._typeaheadHighlighter.bind(this),
            updater: this._typeaheadUpdater.bind(this)
        });

        this.typeahead = this.$el.data('typeahead');
        this.typeahead.$menu.addClass('expression-editor-autocomplete');
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        _.each(this.dataSourceInstances, dataSource => {
            dataSource.$widget.remove();
        });

        delete this.util;
        delete this.typeahead;
        delete this.autocompleteData;
        delete this.dataSource;
        delete this.dataSourceInstances;

        return ExpressionEditorView.__super__.dispose.call(this);
    },

    onChange(e) {
        const isValid = this.isValid();
        this._toggleErrorState(isValid);
        this.trigger('change', e.currentTarget.value, isValid);
    },

    isValid() {
        const value = this.getValue();
        return value === '' || this.util.validate(value);
    },

    _toggleErrorState(isValid) {
        this.$el.toggleClass('error', !isValid);
        this.$el.parent().toggleClass('validation-error', !isValid);
    },

    /**
     * Show autocomplete list
     */
    autocomplete() {
        this.typeahead.lookup();
    },


    /**
     * Sets value to view DOM element
     *
     * @param {string} value
     */
    setValue(value) {
        this.$el.val(value);
        const isValid = this.isValid();
        this._toggleErrorState(isValid);
        this.trigger('change', value, isValid);
    },

    /**
     * Returns value of view DOM element
     *
     * @return {string}
     */
    getValue() {
        return this.$el.val();
    },

    /**
     * Override Typeahead.source function
     *
     * @return {Array}
     * @private
     */
    _typeaheadSource() {
        const expression = this.el.value;
        const position = this.el.selectionStart;

        this.autocompleteData = this.util.getAutocompleteData(expression, position);
        this._toggleDataSource();
        this.typeahead.query = this.autocompleteData.query;

        return _.sortBy(_.keys(this.autocompleteData.items));
    },

    /**
     * Override Typeahead.lookup function
     *
     * @return {Typeahead}
     * @private
     */
    _typeaheadLookup() {
        return this.typeahead.process(this.typeahead.source());
    },

    /**
     * Override Typeahead.select function
     *
     * @return {Typeahead}
     * @private
     */
    _typeaheadSelect() {
        const original = Typeahead.prototype.select;
        const result = original.call(this.typeahead);
        this.typeahead.lookup();
        return result;
    },

    /**
     * Override Typeahead.highlighter function
     *
     * @param {String} item
     * @return {String}
     * @private
     */
    _typeaheadHighlighter(item) {
        const original = Typeahead.prototype.highlighter;
        const suffix = this.autocompleteData.items[item].hasChildren ? '&hellip;' : '';
        return original.call(this.typeahead, item) + suffix;
    },

    /**
     * Override Typeahead.updater function
     *
     * @param {String} item
     * @return {String}
     * @private
     */
    _typeaheadUpdater(item) {
        this.util.updateAutocompleteItem(this.autocompleteData, item);
        const position = this.autocompleteData.position;
        this.$el.one('change', () => {
            // set correct position after typeahead call change event
            this.el.selectionStart = this.el.selectionEnd = position;
        });

        return this.autocompleteData.expression;
    },

    /**
     * Return data source instance by key
     *
     * @param {String} dataSourceKey
     * @return {Object}
     */
    getDataSource(dataSourceKey) {
        return this.dataSourceInstances[dataSourceKey] || this._initializeDataSource(dataSourceKey);
    },

    /**
     * Create data source instance
     *
     * @param {String} dataSourceKey
     * @return {Object}
     * @private
     */
    _initializeDataSource(dataSourceKey) {
        const dataSource = this.dataSourceInstances[dataSourceKey] = {};

        dataSource.$widget = $('<div>').addClass('expression-editor-data-source')
            .html(this.dataSource[dataSourceKey]);
        dataSource.$field = dataSource.$widget.find(':input[name]:first');
        dataSource.active = false;

        this._hideDataSource(dataSource);

        this.$el.after(dataSource.$widget).trigger('content:changed');

        dataSource.$field.on('change', e => {
            if (!dataSource.active) {
                return;
            }

            this.util.updateDataSourceValue(this.autocompleteData, $(e.currentTarget).val());
            this.$el.val(this.autocompleteData.expression)
                .change().focus();

            this.el.selectionStart = this.el.selectionEnd = this.autocompleteData.position;
        });

        return dataSource;
    },

    /**
     * Hide all data sources and show active
     *
     * @private
     */
    _toggleDataSource() {
        this._hideDataSources();

        const dataSourceKey = this.autocompleteData.dataSourceKey;

        if (
            this.autocompleteData.itemsType !== 'datasource' || _.isEmpty(dataSourceKey) ||
            !_.has(this.dataSource, dataSourceKey)
        ) {
            return;
        }

        const dataSourceValue = this.autocompleteData.dataSourceValue;

        this.autocompleteData.items = {}; // hide autocomplete list

        const dataSource = this.getDataSource(dataSourceKey);
        dataSource.$field.val(dataSourceValue).change();

        this._showDataSource(dataSource);
    },

    /**
     * Hide all data sources
     *
     * @private
     */
    _hideDataSources() {
        _.each(this.dataSourceInstances, this._hideDataSource, this);
    },

    /**
     * Hide data source
     *
     * @param {Object} dataSource
     * @private
     */
    _hideDataSource(dataSource) {
        dataSource.active = false;
        dataSource.$widget.hide().removeClass('active');
    },

    /**
     * Show data source
     *
     * @param {Object} dataSource
     * @private
     */
    _showDataSource(dataSource) {
        dataSource.$widget.show().addClass('active');
        dataSource.active = true;
    }
});

export default ExpressionEditorView;
