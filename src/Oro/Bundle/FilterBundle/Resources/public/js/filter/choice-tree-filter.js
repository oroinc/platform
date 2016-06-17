define(function(require) {
    'use strict';

    var ChoiceTreeFilter;
    var _ = require('underscore');
    var TextFilter = require('oro/filter/text-filter');
    var tools = require('oroui/js/tools');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var Select2TreeAutocompleteComponent = require('oroform/js/app/components/select2-tree-autocomplete-component');

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
        },

        emptyValue: {
            type: 1,
            value: ''
        },

        checkedItems: {},

        loadedMetadata: true,

        initialize: function() {
            ChoiceTreeFilter.__super__.initialize.apply(this, arguments);
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
                    multiple: true
                }
            };
            if (this.data) {
                options.configs.data = {
                    results: this.data,
                    text: 'name'
                };
            } else if (this.autocomplete_url) {
                options.url = this.autocomplete_url;
                _.extend(options.configs, {
                    autocomplete_alias: this.autocomplete_alias
                });
            }
            this.select2component = new Select2TreeAutocompleteComponent(options)
        },

        _getListTemplate: function(items, searchQuery) {
            var template;
            var response = [];
            var chain;

            _.each(items, function(value) {
                value.result = false;
            });

            if (searchQuery && searchQuery !== '') {
                response = this.searchEngine.searchItems(searchQuery, items);
                response = this.searchEngine._calculateChain(response, items);
                chain = this._prepareItems(response, items);
                items = chain;
            }

            if (this.mode === availableModes.selected) {
                items = this._getSelectedItems(items);
                var temp = [];
                _.each(items, function(value) {
                    temp.push({
                        value: value,
                        children: []
                    });
                });

                response = temp;
            } else {
                response = this._convertToTree(items);
            }

            template = this.getListTemplate(response);
            return template;
        },

        _getSelectedItems: function(data) {
            var temp = [];
            var values;
            values = this.getValue().value.split(',');
            _.each(values, function(value) {
                _.each(data, function(item) {
                    if (item.id === parseInt(value)) {
                        temp.push(item);
                    }
                });
            });

            return temp;
        },

        _prepareItems: function(response, data) {
            var root = {};
            _.each(response, function(value) {
                root[value.value.id] = value.value;
                root[value.value.id].result = true;
                _.each(value.value.chain, function(item) {
                    _.each(data, function(bu) {
                        if (bu.id === item) {
                            if (!root[bu.id]) {
                                root[bu.id] = bu;
                            }
                        }
                    });
                });
            });

            var rootArray = [];
            for (var i in root) {
                if (root.hasOwnProperty(i)) {
                    rootArray.push(root[i]);
                }
            }

            return rootArray;
        },

        _convertToTree: function(data) {
            var response = [];
            var idToNodeMap = {};
            var element = {};

            _.each(data, function(value) {
                element = {};
                element.value = value;
                element.children = [];

                idToNodeMap[element.value.id] = element;

                if (!element.value.owner_id) {
                    response.push(element);
                } else {
                    var parentNode = idToNodeMap[element.value.owner_id];
                    if (parentNode) {
                        parentNode.children.push(element);
                    } else {
                        response.push(element);
                    }
                }
            });

            return response;
        },

        isSelected: function(item) {
            var value = this.getValue();
            var values = value.value.split(',');

            var response = false;
            _.each(values, function(value) {
                if (parseInt(value) === item.value.id) {
                    response = true;
                }
            });

            return response;
        },

        getListTemplate: function(items) {
            var self = this;
            var template = '<ul>';
            $.each(items, function(key, value) {
                var classSearchResult = '';
                if (value.value.result) {
                    classSearchResult = 'search-result';
                }

                var classSelected = '';
                if (self.isSelected(value)) {
                    classSelected = 'checked';
                }

                var id = self.name + '-' + value.value.id;

                template += '<li>' +
                    '<label for="' + id + '" class="' + classSearchResult + '">' +
                    '<input id="' + id + '" ' +
                    'value="' + value.value.id + '" ' +
                    'type="checkbox" ' + classSelected + '>' +
                    value.value.name +
                    '</label>';
                if (value.children.length > 0) {
                    template += self.getListTemplate(value.children);
                }
                template += '</li>';
            });
            template += '</ul>';

            return template;
        },

        _onChangeBusinessUnit: function(e) {
            var values = [];
            if ($(e.target).is(':checked')) {
                this.checkedItems[$(e.target).val()] = true;
            } else {
                delete this.checkedItems[$(e.target).val()];
            }

            for (var i in this.checkedItems) {
                if (this.checkedItems.hasOwnProperty(i)) {
                    values.push(i);
                }
            }

            values = values.join(',');
            this.setValue({value: values}, true);
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

        /**
         * @inheritDoc
         */
        _getCriteriaHint: function() {
            var self = this;
            var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();
            var option = null;

            if (!value.value) {
                return this.placeholder;
            }

            var values = value.value.split(',');
            var label = [];
            for (var i in values) {
                if (values[i] === 'All') {
                    label.push(values[i]);
                } else {
                    for (var j in self.data) {
                        if (parseInt(values[i]) === this.data[j].id) {
                            label.push(this.data[j].name);
                        }
                    }
                }
            }

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
