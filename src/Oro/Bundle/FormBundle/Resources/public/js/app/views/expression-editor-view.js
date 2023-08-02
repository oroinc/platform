import $ from 'jquery';
import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';

import {EditorState} from '@codemirror/state';
import {EditorView} from '@codemirror/view';

import expressionEditorExtensions from 'oroform/js/app/views/expression-editor-extensions';

const ExpressionEditorView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'dataSource', 'delay', 'util', 'operationButtons'
    ]),

    /**
     * @type {ExpressionEditorUtil}
     */
    util: null,

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
        focus: 'onFocus',
        change: 'onChange'
    },

    /**
     * @inheritdoc
     */
    constructor: function ExpressionEditorView(options) {
        ExpressionEditorView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        if (!options.util) {
            throw new Error('Option `util` is required for `ExpressionEditorView`');
        }

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

        const startState = EditorState.create({
            doc: this.el.value,
            extensions: expressionEditorExtensions({
                util: this.util,
                operationButtons: this.operationButtons,
                setValue: this.setValue.bind(this)
            })
        });

        this.editorView = new EditorView({
            state: startState,
            parent: this.el.parentNode
        });

        this.$el.addClass('hidden-textarea');
        this.$el.after(this.editorView.dom);
    },

    hide() {
        this.editorView.dom.classList.add('hide');
    },

    show() {
        this.editorView.dom.classList.remove('hide');
    },

    onFocus() {
        this.editorView.focus();
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

        this.editorView.destroy();

        delete this.util;
        delete this.editorView;
        delete this.autocompleteData;
        delete this.dataSource;
        delete this.dataSourceInstances;

        return ExpressionEditorView.__super__.dispose.call(this);
    },

    onChange(e) {
        const isValid = this.isValid();
        this._toggleErrorState(isValid);
        this.trigger('change', e.currentTarget.value, isValid);

        this.updateAllContent(e.currentTarget.value);
    },

    /**
     * Update all content in editor view
     * @param {string} content
     */
    updateAllContent(content) {
        const {editorView} = this;

        if (content === editorView.state.doc.toString()) {
            return;
        }

        editorView.dispatch(
            editorView.state.update(
                {
                    changes: {
                        from: 0,
                        to: editorView.state.doc.length,
                        insert: content
                    }
                }
            )
        );
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
     * Sets value to view DOM element
     *
     * @param {string} value
     */
    setValue(value) {
        this.$el.val(value);

        this.updateAllContent(value);

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
