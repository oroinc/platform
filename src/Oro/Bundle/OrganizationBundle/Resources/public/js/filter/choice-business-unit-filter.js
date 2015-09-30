define(function(require) {
    'use strict';

    var ChoiceBusinessUnitFilter;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var TextFilter = require('oro/filter/choice-filter');
    var $ = require('jquery');
    var routing = require('routing');
    var tools = require('oroui/js/tools');
    var messenger = require('oroui/js/messenger');

    var availableModes = {
        all: 'all',
        selected: 'selected'
    };

    /**
     * Number filter: formats value as a number
     *
     * @export  oro/filter/choice-business-unit-filter
     * @class   oro.filter.ChoiceBusinessUnitFilter
     * @extends oro.filter.TextFilter
     */
    ChoiceBusinessUnitFilter = TextFilter.extend({
        templateSelector: '#choice-business-unit-template',

        mode: availableModes.all,

        events: {
            'keyup input': '_onReadCriteriaInputKey',
            'keydown [type="text"]': '_preventEnterProcessing',
            'keyup input[name="search"]': '_onChangeSearchQuery',
            'click .filter-update': '_onClickUpdateCriteria',
            'click .filter-criteria-selector': '_onClickCriteriaSelector',
            'click .filter-criteria .filter-criteria-hide': '_onClickCloseCriteria',
            'click .disable-filter': '_onClickDisableFilter',
            'click .choice-value': '_onClickChoiceValue',
            'click .reset-filter': '_onClickResetFilter',
            'change input[type="checkbox"]': '_onChangeBusinessUnit',
            'click .button-all': '_onClickButtonAll',
            'click .button-selected': '_onClickButtonSelected'
        },

        emptyValue: {
            type: 1,
            value: 'All'
        },

        /**
         * Initialize.
         *
         * @param {Object} options
         */
        initialize: function(options) {
            var self = this;
            if (!self.businessUnit) {
                self.businessUnit = this.data;
            }
            ChoiceBusinessUnitFilter.__super__.initialize.apply(this, arguments);
        },

        _onClickCriteriaSelector: function() {
            ChoiceBusinessUnitFilter.__super__._onClickCriteriaSelector.apply(this, arguments);
            this.$el.find('.list').find('input:first').focus();
        },

        /**
         * @inheritDoc
         */
        _renderCriteria: function() {
            var value = _.extend({}, this.emptyValue, this.value);
            var searchQuery = this.SearchQuery ? this.SearchQuery : undefined;
            var selectedChoiceLabel = '';
            var self = this;

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

            var list = this._getListTemplate(this.businessUnit, searchQuery);
            $filter.find('.list').html(list);

            this._appendFilter($filter);
            this._updateDOMValue();

            this._criteriaRenderd = true;
        },

        _getListTemplate: function(businessUnit, searchQuery) {
            var self = this;
            var template;
            var response = [];
            var chain;

            _.each(businessUnit, function(value) {
                value.result = false;
            });

            if (searchQuery && searchQuery !== '') {
                response = this.searchItems(searchQuery, businessUnit);
                response = this._calculateChain(response, businessUnit);
                chain = this._prepareItems(response, businessUnit);
                businessUnit = chain;
            }

            if (this.mode == availableModes.selected) {
                businessUnit = this._getSelectedItems(businessUnit);
                var temp = [];
                _.each(businessUnit, function(value) {
                    temp .push({
                        value: value,
                        children: []
                    });
                });

                response = temp;
            } else {
                response = this._convertToTree(businessUnit);
            }

            template = this.getListTemplate(response);
            return template;
        },

        _getSelectedItems: function(businessUnit) {
            var temp = [];
            var values;
            values = this.getValue().value.split(',');

            _.each(values, function(value) {
                _.each(businessUnit, function(unit) {
                    if (unit['id'] == value) {
                        temp.push(unit);
                    }
                });
            });

            return temp;
        },

        _prepareItems: function(response, businessUnit) {
            var root = {};
            _.each(response, function(value) {
                root[value.value.id] = value.value;
                root[value.value.id].result = true;
                _.each(value.value.chain, function(item) {
                    _.each(businessUnit, function(bu) {
                        if (bu['id'] == item) {
                            if (!root[bu['id']]) {
                                root[bu['id']] = bu;
                            }
                        }
                    });
                });
            });

            var rootArray = [];
            for (var i in root) {
                rootArray.push(root[i]);
            }

            return rootArray;
        },

        _calculateChain: function(response, businessUnit) {
            var self = this;
            _.each(response, function(value, key) {
                var chain = [];
                chain = self.findChainToRoot(chain, value, businessUnit);
                response[key].value.chain = chain;
            });

            return response;
        },

        _convertToTree: function(data) {
            var self = this;
            var response = [];
            _.each(data, function (value) {
                if (!value['owner_id']) {
                    response.push({
                        value: value,
                        children: []
                    });
                }
            });

            _.each(response, function (value, key) {
                response[key]['children'] = self.findChild(value, data);
            });

            return response;
        },

        searchItems: function(searchQuery, businessUnit) {
            var response = [];
            _.each(businessUnit, function (value) {
                var retusl = value['name'].indexOf(searchQuery);
                if (retusl >= 0) {
                    response.push({
                        value: value,
                        children: []
                    });
                }
            });

            return response;
        },

        findChainToRoot: function(chain, value, businessUnit) {
            var self = this;
            var parent;
            _.each(businessUnit, function(item) {
                if (value.value.owner_id && item['id'] == value.value.owner_id) {
                    parent = {
                        value: item,
                        children: []
                    };
                }
            });

            if (parent) {
                chain.push(parent.value.id);
                self.findChainToRoot(chain, parent, businessUnit);
            }

            return chain;
        },

        isSelected: function(item) {
            var value = this.getValue();
            var values = value.value.split(',');

            var response = false;
            _.each(values, function(value) {
                if (value == item.value.id) {
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

                template += '<li>' +
                '<label for="business-unit-' + value.value.id + '" class="' + classSearchResult + '">' +
                    '<input id="business-unit-' + value.value.id + '" ' +
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

        findChild: function(item, businessUnit) {
            var self = this;
            var responce = [];
            var value = item.value;

            $.each(businessUnit, function(key1, value1) {
                if (value1['owner_id'] === value['id']) {
                    responce.push({
                        value: value1,
                        children: []
                    });
                }
            });

            if (responce.length > 0) {
                $.each(responce, function(key1, value1) {
                    responce[key1]['children'] = self.findChild(value1, businessUnit);
                });
            }

            return responce;
        },

        _onChangeBusinessUnit: function(e) {
            var values = [];
            $.each(this.$el.find('input:checked'), function(key, value) {
                values.push($(this).val());
            });

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
            ChoiceBusinessUnitFilter.__super__._onClickUpdateCriteria.apply(this, arguments);
            this.trigger('update');
            this._updateCriteriaHint();
        },

        /**
         * @inheritDoc
         */
        _readDOMValue: function() {
            var values  = this._getInputValue(this.criteriaValueSelectors.value);

            return {
                value: values,
                type: 1 //this._getInputValue(this.criteriaValueSelectors.type)
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
                    for (var j in self.businessUnit) {
                        if (values[i] == this.businessUnit[j].id) {
                            label.push(this.businessUnit[j].name);
                        }
                    }
                }
            }

            var hintValue = this.wrapHintValue ? ('"' + label.join(',') + '"') : label.join(',');

            return (option ? option.label + ' ' : '') + hintValue;
        },

        _onClickResetFilter: function() {
            ChoiceBusinessUnitFilter.__super__._onClickResetFilter.apply(this, arguments);
            this._updateCriteriaHint();
            this.trigger('update');
            this.$el.find('input:checked').removeAttr('checked');
        },

        _onChangeSearchQuery: function(event) {
            var searchQuery = $(event.target).val();
            var list = this._getListTemplate(this.businessUnit, searchQuery);
            this.$el.find('.list').html(list);
        },

        _onChangeMode: function() {
            this.$el.find('.buttons span').removeClass('active');
            this.$el.find('.button' + this.mode).addClass('active');

            var searchQuery = this.$el.find('[name="search"]').val();

            var list = this._getListTemplate(this.businessUnit, searchQuery);
            this.$el.find('.list').html(list);
        },

        _onClickButtonAll: function(event) {
            if (this.mode != availableModes.all) {
                this.mode = availableModes.all;
                this.$el.find('.buttons span').removeClass('active');

                this._onChangeMode();
                $(event.target).addClass('active');
            }
        },

        _onClickButtonSelected: function(event) {
            if (this.mode != availableModes.selected) {
                this.$el.find('.buttons span').removeClass('active');
                this.mode = availableModes.selected;
                this._onChangeMode();
                $(event.target).addClass('active');
            }
        }
    });

    return ChoiceBusinessUnitFilter;
});
