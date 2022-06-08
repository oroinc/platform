define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    require('bootstrap');
    const BaseView = require('oroui/js/app/views/base/view');
    const ExpressionEditorUtil = require('oroform/js/expression-editor-util');
    const Typeahead = $.fn.typeahead.Constructor;

    const ExpressionEditorView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'dataSource', 'delay'
        ]),

        /**
         * {Object} ExpressionEditorUtil
         */
        util: null,

        /**
         * {Object} Typeahead
         */
        typeahead: null,

        /**
         * {Object} Autocomplete data provided by ExpressionEditorUtil.getAutocompleteData
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
         * {Integer} Validation and autocomplete delay in milliseconds
         */
        delay: 50,

        /**
         * @inheritdoc
         */
        constructor: function ExpressionEditorView(options) {
            ExpressionEditorView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.util = new ExpressionEditorUtil(options);

            this.autocompleteData = this.autocompleteData || {};
            this.dataSource = this.dataSource || {};
            this.dataSourceInstances = this.dataSourceInstances || {};

            this.initAutocomplete();

            if (_.isRTL()) {
                this.$el.attr('dir', 'ltr');
            }

            return ExpressionEditorView.__super__.initialize.call(this, options);
        },

        /**
         * Initialize autocomplete widget
         */
        initAutocomplete: function() {
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
         * @inheritdoc
         */
        delegateEvents: function(events) {
            const result = ExpressionEditorView.__super__.delegateEvents.call(this, events);

            const self = this;
            const namespace = this.eventNamespace();
            const autocomplete = _.debounce(function(e) {
                if (!self.disposed) {
                    self.autocomplete(e);
                }
            }, this.delay);
            const validate = _.debounce(function(e) {
                if (!self.disposed) {
                    self.validate(e);
                }
            }, this.delay);

            this.$el
                .on('focus' + namespace, autocomplete)
                .on('click' + namespace, autocomplete)
                .on('input' + namespace, autocomplete)
                .on('keyup' + namespace, validate)
                .on('change' + namespace, validate)
                .on('blur' + namespace, validate)
                .on('paste' + namespace, validate);

            return result;
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
            this.typeahead.query = this.autocompleteData.itemLastChild;

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
            const hasChild = !!this.autocompleteData.items[item].child;
            const suffix = hasChild ? '&hellip;' : '';
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
            const dataSource = this.dataSourceInstances[dataSourceKey];
            if (!dataSource) {
                return this._initializeDataSource(dataSourceKey);
            }

            return dataSource;
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

            dataSource.$field.on('change', () => {
                if (!dataSource.active) {
                    return;
                }

                this.util.updateDataSourceValue(this.autocompleteData, dataSource.$field.val());
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
            const dataSourceValue = this.autocompleteData.dataSourceValue;

            if (_.isEmpty(dataSourceKey) || !_.has(this.dataSource, dataSourceKey)) {
                return;
            }

            this.autocompleteData.items = {};// hide autocomplete list

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
