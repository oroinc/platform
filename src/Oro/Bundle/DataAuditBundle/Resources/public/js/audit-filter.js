define(function(require) {
    'use strict';

    var AuditFilter;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var DateTimeFilter = require('oro/filter/datetime-filter');
    var ChoiceFilter = require('oro/filter/choice-filter');
    var choiceTemplate = require('tpl!orofilter/templates/filter/embedded/simple-choice-filter.html');
    var template = require('tpl!orodataaudit/templates/audit-filter.html');

    AuditFilter = DateTimeFilter.extend({
        template: template,
        templateSelector: null,
        criteriaValueSelectors: _.extend({}, DateTimeFilter.prototype.criteriaValueSelectors, {
            date_type: '.audit-type-filter-container select[name=datetime]'
        }),
        auditTypeFilterContainerSelector: '.audit-type-filter-container',

        events: function() {
            var events = {
                change: 'applyValue'
            };
            events['change ' + this.auditTypeFilterContainerSelector] = '_onAuditTypeFilterChange';
            return events;
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
            var value = _.extend({}, this.emptyValue, this.getValue());
            var selectedChoiceLabel = this._getSelectedChoiceLabel('choices', value);
            var datePartTemplate = this._getTemplate('fieldTemplate');
            var parts = [];
            parts.push(datePartTemplate({
                name: this.name,
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
