define(function(require) {
    'use strict';

    const template = require('tpl-loader!orofilter/templates/filter/choice-tree.html');
    const _ = require('underscore');
    const $ = require('jquery');
    const TextFilter = require('oro/filter/text-filter');
    const tools = require('oroui/js/tools');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const Select2TreeAutocompleteComponent = require('oro/select2-tree-autocomplete-component');

    /**
     * Number filter: formats value as a number
     *
     * @export  oro/filter/choice-business-unit-filter
     * @class   oro.filter.ChoiceBusinessUnitFilter
     * @extends oro.filter.TextFilter
     */
    const ChoiceTreeFilter = TextFilter.extend({
        template: template,
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

        /**
         * @inheritdoc
         */
        constructor: function ChoiceTreeFilter(options) {
            ChoiceTreeFilter.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ChoiceTreeFilter.__super__.initialize.call(this, options);
            this.data = this.data || [];
            if (this.lazy) {
                this.loadedMetadata = false;
                this.loader(metadata => {
                    this.data = metadata.data;
                    this._updateCriteriaHint();
                    this.loadedMetadata = true;
                    if (this.subview('loading')) {
                        this.subview('loading').hide();
                    }
                });
            }
        },

        render: function() {
            const result = ChoiceTreeFilter.__super__.render.call(this);
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
            ChoiceTreeFilter.__super__._showCriteria.call(this);
        },

        /**
         * @inheritdoc
         */
        _initSelect2Component: function() {
            if (!this.loadedMetadata) {
                return;
            }
            const options = {
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
                const oldValue = this.value;
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
         * @inheritdoc
         */
        _readDOMValue: function() {
            return {
                value: this._getInputValue(this.criteriaValueSelectors.value),
                type: 1
            };
        },

        onDataLoaded: function(e) {
            const results = _.result(e.items, 'results') || [];
            const existIds = _.pluck(this.data, 'id');
            Array.prototype.push.apply(this.data, _.filter(results, function(item) {
                return existIds.indexOf(item.id) === -1;
            }));
        },

        /**
         * @inheritdoc
         */
        _getCriteriaHint: function(...args) {
            const value = (args.length > 0) ? this._getDisplayValue(args[0]) : this._getDisplayValue();
            const option = null;

            if (!value.value) {
                return this.placeholder;
            }

            if (this.data.length === 0) {
                this.loadDataById(value);
            } else {
                const renderedPropertyName = this.renderedPropertyName || 'name';
                const label = [];
                _.each(value.value.split(','), function(val) {
                    let id = parseInt(val);
                    if (val && isNaN(id)) {
                        id = val;
                    }

                    const item = _.findWhere(this.data, {id: id});
                    if (item !== void 0) {
                        if (item.treePath) {
                            const path = [];
                            _.each(item.treePath, function(item) {
                                path.push(item[renderedPropertyName]);
                            });
                            label.push(path.join(' / '));
                        } else {
                            label.push(item[renderedPropertyName]);
                        }
                    }
                }, this);

                if (this.select2component && this.select2component.view.$el.select2('data').length === 0) {
                    this.select2component.view.$el.select2('data', this.data);
                }

                const hintValue = this.wrapHintValue ? ('"' + label.join(', ') + '"') : label.join(', ');
                return (option ? option.label + ' ' : '') + hintValue;
            }
        },

        getQuery: function(value) {
            let query;
            if (typeof value.value === 'string') {
                query = value.value;
            } else {
                const ids = [];
                _.each(value.value, function(val) {
                    ids.push(val.id);
                });
                query = ids.join(',');
            }

            return query;
        },

        loadDataById: function(value) {
            const query = this.getQuery(value);
            const self = this;
            $.ajax({
                url: this.autocomplete_url,
                data: {
                    page: 1,
                    per_page: 10,
                    name: self.autocomplete_alias,
                    query: query,
                    search_by_id: true
                },
                success: function(reposne) {
                    self.data = reposne.results;
                    self._updateCriteriaHint(true);
                }
            });
        },

        reset: function() {
            ChoiceTreeFilter.__super__.reset.call(this);
            this.$(this.criteriaValueSelectors.value).trigger('change');
            this._hideCriteria();
        }
    });

    return ChoiceTreeFilter;
});
