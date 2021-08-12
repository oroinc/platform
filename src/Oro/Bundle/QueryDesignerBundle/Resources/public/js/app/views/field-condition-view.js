define(function(require) {
    'use strict';

    // @export oroquerydesigner/js/app/views/field-condition-view

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const loadModules = require('oroui/js/app/services/load-modules');
    const mapFilterModuleName = require('orofilter/js/map-filter-module-name');
    const AbstractConditionView = require('oroquerydesigner/js/app/views/abstract-condition-view');
    const FieldChoiceView = require('oroentity/js/app/views/field-choice-view');

    const FieldConditionView = AbstractConditionView.extend({
        /**
         * @inheritdoc
         */
        constructor: function FieldConditionView(options) {
            FieldConditionView.__super__.constructor.call(this, options);
        },

        getDefaultOptions: function() {
            const defaultOptions = FieldConditionView.__super__.getDefaultOptions.call(this);
            return _.extend({}, defaultOptions, {
                fieldChoice: {},
                hierarchy: []
            });
        },

        _renderFilter: function(fieldId) {
            const filterOptions = this._createFilterOptions(fieldId);
            const moduleName = mapFilterModuleName(filterOptions.type);
            const requires = [moduleName];

            if (filterOptions.init_module) {
                requires.push(filterOptions.init_module);
            }

            // show loading message, if loading takes more than 100ms
            const showLoadingTimeout = setTimeout(() => {
                this.$filterContainer.html('<span class="loading-indicator">' + __('Loading...') + '</span>');
            }, 100);

            loadModules(requires, function(Filter, optionResolver) {
                const appendFilter = () => {
                    clearTimeout(showLoadingTimeout);
                    const filter = new (Filter.extend(filterOptions))();
                    if (!this.disposed) {
                        this._appendFilter(filter);
                    }
                };
                if (optionResolver) {
                    const promise = optionResolver(filterOptions, this.subview('choice-input').splitFieldId(fieldId));
                    promise.done(appendFilter);
                } else {
                    appendFilter();
                }
            }, this);
        },

        _createFilterOptions: function(fieldId) {
            let filterOptions;
            const conditions = this.getApplicableConditions(fieldId);

            if (!_.isEmpty(conditions) && !(conditions.entity === 'Oro\\Bundle\\AccountBundle\\Entity\\Account' &&
                conditions.field === 'lifetimeValue')) {
                filterOptions = _.clone(this.options.filters[this._getApplicableFilterId(conditions)]);
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

        getApplicableConditions: function(fieldId) {
            return this.subview('choice-input').getApplicableConditions(fieldId);
        },

        initChoiceInputView: function() {
            const fieldChoiceView = new FieldChoiceView(_.extend({
                autoRender: true,
                el: this.$choiceInput,
                entity: this.options.rootEntity
            }, this.options.fieldChoice));
            return $.when(fieldChoiceView.deferredRender);
        }
    });

    return FieldConditionView;
});
