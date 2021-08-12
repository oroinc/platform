define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const DateTimeFilter = require('oro/filter/datetime-filter');
    const ChoiceFilter = require('oro/filter/choice-filter');
    const choiceTemplate = require('tpl-loader!orofilter/templates/filter/embedded/simple-choice-filter.html');
    const template = require('tpl-loader!orodataaudit/templates/audit-filter.html');

    const AuditFilter = DateTimeFilter.extend({
        template: template,
        templateSelector: null,
        criteriaValueSelectors: _.extend({}, DateTimeFilter.prototype.criteriaValueSelectors, {
            date_type: 'select[name=audit]',
            type: 'select[name=audit]',
            date_part: '.audit-filter-container [name=datetime_part]',
            value: {
                start: '.audit-filter-container input[name="start"]',
                end: '.audit-filter-container input[name="end"]'
            }
        }),

        choiceDropdownSelector: '.audit-filter-type .dropdown-menu',

        selectors: {
            startContainer: '.audit-filter-container .filter-start-date',
            separator: '.audit-filter-container .filter-separator',
            endContainer: '.audit-filter-container .filter-end-date'
        },

        auditTypeFilterContainerSelector: '.audit-type-filter-container',

        events: function() {
            const events = {
                change: 'applyValue'
            };
            events['change ' + this.auditTypeFilterContainerSelector] = '_onAuditTypeFilterChange';
            return events;
        },

        /**
         * @inheritdoc
         */
        constructor: function AuditFilter(options) {
            AuditFilter.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            _.extend(this, _.pick(options, 'auditFilterType'));
            AuditFilter.__super__.initialize.call(this, options);
        },

        render: function() {
            AuditFilter.__super__.render.call(this);
            this._renderAuditTypeFilter();
            return this;
        },

        _renderAuditTypeFilter: function() {
            this.auditTypeFilter = new ChoiceFilter({
                caret: '',
                template: choiceTemplate,
                choices: {
                    changed: __('oro.dataaudit.data_audit_condition.changed'),
                    changed_to_value: __('oro.dataaudit.data_audit_condition.changed_to_value')
                }
            });
            this.auditTypeFilter.setValue({
                type: this.auditFilterType
            });
            this.$el.find(this.auditTypeFilterContainerSelector).empty().append(this.auditTypeFilter.render().$el);
        },

        onChangeFilterType: function(e) {
            if (!$.contains(this.getOriginalFilterContainer().get(0), e.currentTarget)) {
                AuditFilter.__super__.onChangeFilterType.call(this, e);
            }
        },

        _onClickChoiceValue: function(e) {
            if (!$.contains(this.getOriginalFilterContainer().get(0), e.currentTarget)) {
                AuditFilter.__super__._onClickChoiceValue.call(this, e);
            }
        },

        _onAuditTypeFilterChange: function() {
            this.auditTypeFilter.applyValue();
            this.trigger('audit-type-change');
        },

        getAuditType: function() {
            if (this.auditTypeFilter) {
                return this.auditTypeFilter.value.type;
            }
        },

        _getParts: function() {
            const value = _.extend({}, this.emptyValue, this.getValue());
            const selectedChoiceLabel = this._getSelectedChoiceLabel('choices', value);
            const datePartTemplate = this._getTemplate('fieldTemplate');
            const parts = [];
            parts.push(datePartTemplate({
                name: 'audit',
                choices: this.choices,
                selectedChoice: value.type,
                selectedChoiceLabel: selectedChoiceLabel,
                popoverContent: __('oro.filter.date.info')
            }));

            return parts;
        },

        wrapFilter: function(innerFilterElement) {
            innerFilterElement.after(this.$el);
            this.getOriginalFilterContainer().empty().append(innerFilterElement);
        },

        getOriginalFilterContainer: function() {
            return this.$('.inner-filter-container');
        }
    });

    return AuditFilter;
});
