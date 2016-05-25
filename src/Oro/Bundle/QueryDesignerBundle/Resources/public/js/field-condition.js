define(['jquery', 'underscore', 'orotranslation/js/translator', 'orofilter/js/map-filter-module-name',
    'oroentity/js/field-choice', 'jquery-ui'
    ], function($, _, __, mapFilterModuleName) {
    'use strict';

    /**
     * Compare field widget
     */
    $.widget('oroquerydesigner.fieldCondition', {
        options: {
            fieldChoice: {},
            fieldChoiceClass: 'select',
            filters: [],
            filterContainerClass: 'active-filter',
            hierarchy: []
        },

        _create: function() {
            var data = this.element.data('value');

            this.$fieldChoice = $('<input>').addClass(this.options.fieldChoiceClass);
            this.$filterContainer = $('<span>').addClass(this.options.filterContainerClass);
            this.element.append(this.$fieldChoice, this.$filterContainer);

            this.$fieldChoice.fieldChoice(this.options.fieldChoice);

            if (data && data.columnName) {
                this.selectField(data.columnName);
                this._renderFilter(data.columnName);
            }

            this._on(this.$fieldChoice, {
                changed: function(e, fieldId) {
                    $(':focus').blur();
                    // reset current value on field change
                    this.element.data('value', {});
                    this._renderFilter(fieldId);
                    e.stopPropagation();
                }
            });

            this._on(this.$filterContainer, {
                change: function(e) {
                    if (this.filter && !$(e.target).is('[data-fake-front-field]')) {
                        this.filter.applyValue();
                    }
                }
            });
        },

        _destroy: function() {
            if (this.filter) {
                this.filter.dispose();
                delete this.filter;
            }
        },

        _getCreateOptions: function() {
            return $.extend(true, {}, this.options);
        },

        _renderFilter: function(fieldId) {
            var filter;
            var conditions = this.$fieldChoice.fieldChoice('getApplicableConditions', fieldId);

            if (_.isEmpty(conditions)) {
                filter = {
                    type: 'none',
                    applicable: {},
                    popupHint: '<span class="deleted-field">' + __('oro.querydesigner.field_not_found') + '</span>'
                };
            } else {
                filter = this.options.filters[this._getApplicableFilterId(conditions)];
            }

            if (!filter) {
                filter = {
                    type: 'none',
                    applicable: {},
                    popupHint: '<span class="deleted-field">' +
                        __('oro.querydesigner.field_condition.filter_not_supported') + '</span>'
                };
            }

            // @TODO temporary workaround. Will by fixed in BAP-8112
            if (conditions.entity === 'OroCRM\\Bundle\\AccountBundle\\Entity\\Account' &&
                conditions.field === 'lifetimeValue') {
                filter = {
                    type: 'none',
                    applicable: {},
                    popupHint: '<span class="deleted-field">' +
                        __('oro.querydesigner.field_condition.lifetimevalue_filter_not_supported') + '</span>'
                };
            }

            this._createFilter(filter, fieldId);
        },

        _getApplicableFilterId: function(conditions) {
            var filterId = null;
            var matchedBy = null;
            var self = this;

            if (!_.isUndefined(conditions.filter)) {
                // the criteria parameter represents a filter
                return conditions.filter;
            }

            _.each(this.options.filters, function(filter, id) {
                var matched;

                if (!_.isEmpty(filter.applicable)) {
                    // check if a filter conforms the given criteria
                    matched = self._matchApplicable(filter.applicable, conditions);
                    if (matched && (
                            _.isNull(matchedBy) ||
                            // new rule is more exact
                            _.size(matchedBy) < _.size(matched) ||
                            // 'type' rule is most low level one, so any other rule can override it
                            (_.size(matchedBy) === 1 && _.has(matchedBy, 'type'))
                        )) {
                        matchedBy = matched;
                        filterId = id;
                    }
                }
            });

            return filterId;
        },

        _matchApplicable: function(applicable, criteria) {
            var hierarchy = this.options.hierarchy[criteria.entity];
            return _.find(applicable, function(item) {
                return _.every(item, function(value, key) {
                    if (key === 'entity' && hierarchy.length) {
                        return _.indexOf(hierarchy, criteria[key]);
                    }
                    return criteria[key] === value;
                });
            });
        },

        _createFilter: function(filterOptions, fieldId) {
            var moduleName = mapFilterModuleName(filterOptions.type);
            var requires = [moduleName];

            if (filterOptions.init_module) {
                requires.push(filterOptions.init_module);
            }

            // show loading message, if loading takes more than 100ms
            var showLoadingTimeout = setTimeout(_.bind(function() {
                this.$filterContainer.html('<span class="loading-indicator">' + __('Loading...') + '</span>');
            }, this), 100);

            require(requires, _.bind(function(Filter, optionResolver) {
                var fieldCondition = this;
                function appendFilter() {
                    clearTimeout(showLoadingTimeout);
                    var filter = new (Filter.extend(filterOptions))();
                    fieldCondition._appendFilter(filter);
                }
                if (optionResolver) {
                    var promise = optionResolver(filterOptions, this.$fieldChoice.fieldChoice('splitFieldId', fieldId));
                    promise.done(appendFilter);
                } else {
                    appendFilter();
                }
            }, this));
        },

        _appendFilter: function(filter) {
            var value = this.element.data('value');
            this.filter = filter;

            if (value && value.criterion) {
                this.filter.value = value.criterion.data;
            }

            this.filter.render();
            this.$filterContainer.empty().append(this.filter.$el);

            this.filter.on('update', _.bind(this._onUpdate, this));
            this._onUpdate();
        },

        _onUpdate: function() {
            var value;

            if (this.filter && !this.filter.isEmptyValue()) {
                value = {
                    columnName: this.element.find('input.select').inputWidget('val'),
                    criterion: this._getFilterCriterion()
                };
            } else {
                value = {};
            }

            this.element.data('value', value);
            this.element.trigger('changed');
        },

        _getFilterCriterion: function() {
            var data = this.filter.getValue();

            if (this.filter.filterParams) {
                data.params = this.filter.filterParams;
            }

            return {
                filter: this.filter.name,
                data: data
            };
        },

        selectField: function(name) {
            this.$fieldChoice.fieldChoice('setValue', name);
        }
    });

    return $;
});
