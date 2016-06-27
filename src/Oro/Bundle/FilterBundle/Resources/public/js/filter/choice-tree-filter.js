define(function(require) {
    'use strict';

    var ChoiceTreeFilter;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var TextFilter = require('oro/filter/choice-filter');
    var $ = require('jquery');
    var tools = require('oroui/js/tools');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');

    var availableModes = {
        all: 'all',
        selected: 'selected'
    };

    var searchEngine = {
        findChild: function(item, items) {
            var self = this;
            var responce = [];

            _.each(items, function(value) {
                if (value.owner_id === item.value.id) {
                    responce.push({
                        value: value,
                        children: []
                    });
                }
            });

            if (responce.length > 0) {
                $.each(responce, function(key, value) {
                    responce[key].children = self.findChild(value, items);
                });
            }

            return responce;
        },
        _calculateChain: function(response, items) {
            var self = this;
            _.each(response, function(value, key) {
                var chain = [];
                chain = self.findChainToRoot(chain, value, items);
                response[key].value.chain = chain;
            });

            return response;
        },
        findChainToRoot: function(chain, value, items) {
            var self = this;
            var parent;
            _.each(items, function(item) {
                if (value.value.owner_id && item.id === value.value.owner_id) {
                    parent = {
                        value: item,
                        children: []
                    };
                }
            });

            if (parent) {
                chain.push(parent.value.id);
                self.findChainToRoot(chain, parent, items);
            }

            return chain;
        },
        searchItems: function(searchQuery, items) {
            searchQuery = searchQuery.toLowerCase();
            var response = [];
            _.each(items, function(value) {
                var result = value.name.toLowerCase().indexOf(searchQuery);
                if (result >= 0) {
                    response.push({
                        value: value,
                        children: []
                    });
                }
            });

            return response;
        }
    };

    /**
     * Number filter: formats value as a number
     *
     * @export  oro/filter/choice-business-unit-filter
     * @class   oro.filter.ChoiceBusinessUnitFilter
     * @extends oro.filter.TextFilter
     */
    ChoiceTreeFilter = TextFilter.extend({
        templateSelector: '#choice-tree-template',

        mode: availableModes.all,

        events: {
            'keyup input': '_onReadCriteriaInputKey',
            'keydown [type="text"]': '_preventEnterProcessing',
            'keyup input[name="search"]': '_onChangeSearchQuery',
            'click .filter-update': '_onClickUpdateCriteria',
            'click .filter-criteria .filter-criteria-hide': '_onClickCloseCriteria',
            'click .disable-filter': '_onClickDisableFilter',
            'click .choice-value': '_onClickChoiceValue',
            'change input[type="checkbox"]': '_onChangeBusinessUnit',
            'click .button-all': '_onClickButtonAll',
            'click .button-selected': '_onClickButtonSelected'
        },

        emptyValue: {
            type: 1,
            value: __('All')
        },

        searchEngine: searchEngine,

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

        /**
         * @inheritDoc
         */
        _renderCriteria: function() {
            if (!this.loadedMetadata) {
                return;
            }

            var value = _.extend({}, this.emptyValue, this.value);
            var searchQuery = this.SearchQuery ? this.SearchQuery : undefined;
            var selectedChoiceLabel = '';

            if (!_.isEmpty(this.choices)) {
                var foundChoice = _.find(this.choices, function(choice) {
                    return (choice.value === value.type);
                });

                if (foundChoice) {
                    selectedChoiceLabel = foundChoice.label;
                }
            }

            var $filter = $(this.template({
                name: this.name,
                choices: this.choices,
                selectedChoice: value.type,
                selectedChoiceLabel: selectedChoiceLabel,
                value: value.value
            }));

            var list = this._getListTemplate(this.data, searchQuery);
            $filter.find('.list').html(list);

            this._appendFilter($filter);
            this._updateDOMValue();
            this._initCheckedItems();

            this._criteriaRenderd = true;
        },

        _initCheckedItems: function() {
            var self = this;
            var value = this.getValue();
            var temp;
            if (value.value.length > 0) {
                temp = value.value.split(',');

                _.each(temp, function(value) {
                    if (value !== 'All') {
                        self.checkedItems[value] = true;
                    }
                });
            }
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
                template += '<li>' +
                    '<label class="' + (value.value.result ? 'search-result' : '') + '">' +
                    '<input ' +
                    'value="' + value.value.id + '" ' +
                    'type="checkbox" ' + (self.isSelected(value) ? 'checked' : '') + '>' +
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
                if (values[i] === __('All')) {
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
            this._hideCriteria();
            this.checkedItems = {};
            this.$el.find('input[name="search"]').val('');
            this.$el.find('input:checked').removeAttr('checked');
            this.$el.find('label').removeClass('search-result');
        },

        _onChangeSearchQuery: function(event) {
            var searchQuery = $(event.target).val();
            var list = this._getListTemplate(this.data, searchQuery);
            this.$el.find('.list').html(list);
        },

        _onChangeMode: function() {
            this.$el.find('.buttons span').removeClass('active');
            this.$el.find('.button' + this.mode).addClass('active');

            var searchQuery = this.$el.find('[name="search"]').val();

            var list = this._getListTemplate(this.data, searchQuery);
            this.$el.find('.list').html(list);
        },

        _onClickButtonAll: function(event) {
            if (this.mode !== availableModes.all) {
                this.mode = availableModes.all;
                this.$el.find('.buttons span').removeClass('active');

                this._onChangeMode();
                $(event.target).addClass('active');
            }
        },

        _onClickButtonSelected: function(event) {
            event.stopImmediatePropagation();
            if (this.mode !== availableModes.selected) {
                this.$el.find('.buttons span').removeClass('active');
                this.mode = availableModes.selected;
                this._onChangeMode();
                $(event.target).addClass('active');
            }
        },

        _focusCriteria: function() {
            this.$el.find('.list').find('input:first').focus();
        }
    });

    return ChoiceTreeFilter;
});
