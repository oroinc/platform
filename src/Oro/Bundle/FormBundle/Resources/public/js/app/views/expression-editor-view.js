import $ from 'jquery';
import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';

import {EditorState} from '@codemirror/state';
import {EditorView} from '@codemirror/view';

import expressionEditorExtensions from 'oroform/js/app/views/expression-editor-extensions';

const ExpressionEditorView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'dataSource', 'interactionDelay', 'util', 'operationButtons',
        'linterDelay'
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
    delay: 0,

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
            doc: this.util.normalizePropertyNamesExpression(this.el.value),
            extensions: expressionEditorExtensions({
                util: this.util,
                operationButtons: this.operationButtons,
                interactionDelay: this.interactionDelay,
                linterDelay: this.linterDelay,
                dataSource: this.dataSource,
                getDataSourceCallback: this.showDataSourceElement.bind(this)
            }).concat([
                EditorView.updateListener.of(this.editorUpdateListener.bind(this)),
                EditorView.editorAttributes.of({
                    'data-name': this.$el.attr('name')
                })
            ])
        });

        this.editorView = new EditorView({
            state: startState,
            parent: this.el.parentNode
        });

        this.$el.addClass('hidden-textarea');
        this.$el.after(this.editorView.dom);
    },

    editorUpdateListener(event) {
        const {state} = event;
        const {to} = state.selection.ranges[0];
        const content = this.util.unNormalizePropertyNamesExpression(state.doc.toString());
        if (content !== this.getValue()) {
            this.setValue(content);
        }

        this.autocompleteData = this.util.getAutocompleteData(content, to);
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

        const normalizedContent = this.util.normalizePropertyNamesExpression(content);

        editorView.dispatch(
            editorView.state.update(
                {
                    changes: {
                        from: 0,
                        to: editorView.state.doc.length,
                        insert: normalizedContent
                    },
                    selection: {
                        anchor: normalizedContent.length
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
    setValue(value, sync = true) {
        this.$el.val(value).trigger('change');

        sync && this.updateAllContent(value);

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
        dataSource.$field = dataSource.$widget.find(':input[name]').first();
        dataSource.active = false;

        this._hideDataSource(dataSource);

        dataSource.$field.on('change', e => {
            if (!dataSource.active) {
                return;
            }

            this.util.updateDataSourceValue(this.autocompleteData, $(e.currentTarget).val());
            this.$el.val(this.autocompleteData.expression)
                .trigger('change');

            this.el.selectionStart = this.el.selectionEnd = this.autocompleteData.position;
        });

        return dataSource;
    },

    showDataSourceElement(dataSourceKey, dataSourceValue) {
        this._hideDataSources();

        const dataSource = this.getDataSource(dataSourceKey);
        dataSource.$field.val(dataSourceValue).trigger('change');

        this._showDataSource(dataSource);

        return dataSource;
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
