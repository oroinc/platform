define(function(require) {
    'use strict';

    var FieldConditionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var tools = require('oroui/js/tools');
    var mapFilterModuleName = require('orofilter/js/map-filter-module-name');
    var AbstractConditionView = require('oroquerydesigner/js/app/views/abstract-condition-view');

    require('oroentity/js/field-choice');

    FieldConditionView = AbstractConditionView.extend({
        getDefaultOptions: function() {
            var defaultOptions = FieldConditionView.__super__.getDefaultOptions.call(this);
            return _.extend({}, defaultOptions, {
                fieldChoice: {},
                hierarchy: []
            });
        },

        _renderFilter: function(fieldId) {
            var deferred = $.Deferred();
            var filterOptions = this._createFilterOptions(fieldId);
            var moduleName = mapFilterModuleName(filterOptions.type);
            var requires = [moduleName];

            if (filterOptions.init_module) {
                requires.push(filterOptions.init_module);
            }

            // show loading message, if loading takes more than 100ms
            var showLoadingTimeout = setTimeout(_.bind(function() {
                this.$filterContainer.html('<span class="loading-indicator">' + __('Loading...') + '</span>');
            }, this), 100);

            tools.loadModules(requires, _.bind(function(modules) {
                var Filter = _.first(modules);
                var appendFilter = _.bind(function() {
                    clearTimeout(showLoadingTimeout);
                    var filter = new (Filter.extend(filterOptions))();
                    this._appendFilter(filter);
                    deferred.resolve();
                }, this);
                if (modules.length > 1) {
                    var optionResolver = modules[1];
                    var promise = optionResolver(filterOptions, this.getChoiceInputWidget().splitFieldId(fieldId));
                    promise.done(appendFilter);
                } else {
                    appendFilter();
                }
            }, this));
            return deferred.promise();
        },

        _createFilterOptions: function(fieldId) {
            var filterOptions;
            var conditions = this.getChoiceInputWidget().getApplicableConditions(fieldId);

            if (!_.isEmpty(conditions) && !(conditions.entity === 'Oro\\Bundle\\AccountBundle\\Entity\\Account' &&
                conditions.field === 'lifetimeValue')) {
                filterOptions = this.options.filters[this._getApplicableFilterId(conditions)];
            }

            if (!filterOptions) {
                filterOptions = {
                    type: 'none',
                    applicable: {},
                    popupHint: '<span class="deleted-field">' +
                    __('oro.querydesigner.field_condition.filter_not_supported') + '</span>'
                };
            }

            return filterOptions;
        },

        initChoiceInput: function() {
            this.$choiceInput.fieldChoice(this.options.fieldChoice);
        },

        getChoiceInputWidget: function() {
            return this.$choiceInput.fieldChoice('instance');
        }
    });

    return FieldConditionView;
});
