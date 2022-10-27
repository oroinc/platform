define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    require('bootstrap');
    const BaseView = require('oroui/js/app/views/base/view');
    const ExpressionEditorUtil = require('oroform/js/expression-editor-util').default;
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
            keyup: 'debouncedValidate',
            change: 'debouncedValidate',
            blur: 'debouncedValidate',
            paste: 'debouncedValidate'
        },

        /**
         * @inheritdoc
         */
        constructor: function ExpressionEditorView(options) {
            this.debouncedAutocomplete = _.debounce(function(e) {
                if (!this.disposed) {
                    this.autocomplete(e);
                }
            }.bind(this), this.delay);
            this.debouncedValidate = _.debounce(function(e) {
                if (!this.disposed) {
                    this.validate(e);
                }
            }.bind(this), this.delay);
            ExpressionEditorView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            const utilOptions = _.pick(options,
                'itemLevelLimit', 'allowedOperations', 'operations', 'rootEntities', 'entityDataProvider');
            utilOptions.dataSourceNames = _.keys(options.dataSource);
            this.util = new ExpressionEditorUtil(utilOptions);

            this.autocompleteData = this.autocompleteData || {};
            this.dataSource = this.dataSource || {};
            this.dataSourceInstances = this.dataSourceInstances || {};

            if (_.isRTL()) {
                this.$el.attr('dir', 'ltr');
            }

            return ExpressionEditorView.__super__.initialize.call(this, options);
        },

        render: function() {
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
        dispose: function() {
            if (this.disposed) {
                return;
            }

            _.each(this.dataSourceInstances, function(dataSource) {
                dataSource.$widget.remove();
            });

            delete this.util;
            delete this.typeahead;
            delete this.autocompleteData;
            delete this.dataSource;
            delete this.dataSourceInstances;

            return ExpressionEditorView.__super__.dispose.call(this);
        },

        /**
         * Show autocomplete list
         */
        autocomplete: function() {
            this.typeahead.lookup();
        },

        /**
         * Validate expression
         */
        validate: function() {
            const isValid = this.util.validate(this.$el.val());
            this.$el.toggleClass('error', !isValid);
            this.$el.parent().toggleClass('expression-error', !isValid);
        },

        /**
         * Override Typeahead.source function
         *
         * @return {Array}
         * @private
         */
        _typeaheadSource: function() {
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
        _typeaheadLookup: function() {
            return this.typeahead.process(this.typeahead.source());
        },

        /**
         * Override Typeahead.select function
         *
         * @return {Typeahead}
         * @private
         */
        _typeaheadSelect: function() {
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
        _typeaheadHighlighter: function(item) {
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
        _typeaheadUpdater: function(item) {
            this.util.updateAutocompleteItem(this.autocompleteData, item);
            const position = this.autocompleteData.position;
            this.$el.one('change', function() {
                // set correct position after typeahead call change event
                this.selectionStart = this.selectionEnd = position;
            });

            return this.autocompleteData.expression;
        },

        /**
         * Return data source instance by key
         *
         * @param {String} dataSourceKey
         * @return {Object}
         */
        getDataSource: function(dataSourceKey) {
            return this.dataSourceInstances[dataSourceKey] || this._initializeDataSource(dataSourceKey);
        },

        /**
         * Create data source instance
         *
         * @param {String} dataSourceKey
         * @return {Object}
         * @private
         */
        _initializeDataSource: function(dataSourceKey) {
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
        _toggleDataSource: function() {
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
        _hideDataSources: function() {
            _.each(this.dataSourceInstances, this._hideDataSource, this);
        },

        /**
         * Hide data source
         *
         * @param {Object} dataSource
         * @private
         */
        _hideDataSource: function(dataSource) {
            dataSource.active = false;
            dataSource.$widget.hide().removeClass('active');
        },

        /**
         * Show data source
         *
         * @param {Object} dataSource
         * @private
         */
        _showDataSource: function(dataSource) {
            dataSource.$widget.show().addClass('active');
            dataSource.active = true;
        }
    });

    return ExpressionEditorView;
});
