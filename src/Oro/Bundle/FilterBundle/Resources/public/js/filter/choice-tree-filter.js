define(function(require) {
    'use strict';

    var ChoiceTreeFilter;
    var _ = require('underscore');
    var TextFilter = require('oro/filter/text-filter');
    var tools = require('oroui/js/tools');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var Select2TreeAutocompleteComponent = require('oro/select2-tree-autocomplete-component');

    /**
     * Number filter: formats value as a number
     *
     * @export  oro/filter/choice-business-unit-filter
     * @class   oro.filter.ChoiceBusinessUnitFilter
     * @extends oro.filter.TextFilter
     */
    ChoiceTreeFilter = TextFilter.extend({
        templateSelector: '#choice-tree-template',

        select2component: null,

        events: {
            'click .filter-update': '_onClickUpdateCriteria',
            'click .filter-criteria .filter-criteria-hide': '_onClickCloseCriteria',
            'click .disable-filter': '_onClickDisableFilter',
            'select2-data-loaded': 'onDataLoaded',
            'select2-loaded': 'onDataLoaded'
        },

        emptyValue: {
            type: 1,
            value: ''
        },

        checkedItems: {},

        loadedMetadata: true,

        initialize: function() {
            ChoiceTreeFilter.__super__.initialize.apply(this, arguments);
            this.data = this.data || [];
            if (this.lazy) {
                this.loadedMetadata = false;
                this.loader(
                    _.bind(function(metadata) {
                        this.data = metadata.data;
                        this._updateCriteriaHint();
                        this.loadedMetadata = true;
                        if (this.subview('loading')) {
                            this.subview('loading').hide();
                        }
                    }, this)
                );
            }
        },

        render: function() {
            var result = ChoiceTreeFilter.__super__.render.apply(this, arguments);
            if (!this.loadedMetadata) {
                this.subview('loading', new LoadingMaskView({
                    container: this.$el
                }));
                this.subview('loading').show();
            }
            return result;
        },
        _showCriteria: function() {
            if (!this.select2component) {
                this._initSelect2Component();
            }
            ChoiceTreeFilter.__super__._showCriteria.apply(this, arguments);
        },
        /**
         * @inheritDoc
         */
        _initSelect2Component: function() {
            if (!this.loadedMetadata) {
                return;
            }
            var options = {
                _sourceElement: this.$(this.criteriaValueSelectors.value),
                configs: {
                    allowClear: true,
                    minimumInputLength: 0,
                    multiple: true,
                    renderedPropertyName: this.renderedPropertyName,
                    forceSelectedData: true
                }
            };
            if (this.autocomplete_url) {
                options.url = this.autocomplete_url;
                _.extend(options.configs, {
                    autocomplete_alias: this.autocomplete_alias
                });
            } else if (this.data) {
                options.configs.data = {
                    results: this.data,
                    text: 'name'
                };
            }
            this.$(this.criteriaValueSelectors.value).data('selected-data', this.data);
            this.select2component = new Select2TreeAutocompleteComponent(options);

        },

        /**
         * Set raw value to filter
         *
         * @param value
         * @param skipRefresh
         * @return {*}
         */
        setValue: function(value, skipRefresh) {
            if (!tools.isEqualsLoosely(this.value, value)) {
                var oldValue = this.value;
                this.value = tools.deepClone(value);
                this._updateDOMValue();
                if (!skipRefresh) {
                    this._onValueUpdated(this.value, oldValue);
                }
            }
            return this;
        },

        _onClickUpdateCriteria: function() {
            this.trigger('updateCriteriaClick', this);
            this._hideCriteria();
            this.applyValue();
        },

        /**
         * @inheritDoc
         */
        _readDOMValue: function() {
            return {
                value: this._getInputValue(this.criteriaValueSelectors.value),
                type: 1
            };
        },

        onDataLoaded: function(e) {
            var results = _.result(e.items, 'results') || [];
            var existIds = _.pluck(this.data, 'id');
            Array.prototype.push.apply(this.data, _.filter(results, function(item) {
                return existIds.indexOf(item.id) === -1;
            }));
        },

        /**
         * @inheritDoc
         */
        _getCriteriaHint: function() {
            var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();
            var option = null;

            if (!value.value) {
                return this.placeholder;
            }

            var renderedPropertyName = this.renderedPropertyName || 'name';
            var label = [];
            _.each(value.value.split(','), function(val) {
                var item = _.findWhere(this.data, {id: parseInt(val)});
                if (item !== void 0) {
                    label.push(item[renderedPropertyName]);
                }
            }, this);

            var hintValue = this.wrapHintValue ? ('"' + label.join(',') + '"') : label.join(',');
            return (option ? option.label + ' ' : '') + hintValue;
        },

        reset: function() {
            ChoiceTreeFilter.__super__.reset.apply(this, arguments);
            this.$(this.criteriaValueSelectors.value).trigger('change');
            this._hideCriteria();
        }
    });

    return ChoiceTreeFilter;
});
