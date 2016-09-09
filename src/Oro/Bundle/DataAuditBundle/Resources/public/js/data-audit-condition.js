define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oro/filter/datetime-filter',
    'oro/filter/choice-filter',
    'oroentity/js/field-choice',
    'oroquerydesigner/js/field-condition'
], function($, _, __, DateTimeFilter, ChoiceFilter) {
    'use strict';

    $.widget('oroauditquerydesigner.dataAuditCondition', $.oroquerydesigner.fieldCondition, {
        options: {
            changeStateTpl: _.template($('#template-audit-condition-type-select').html())
        },

        _create: function() {
            this._superApply(arguments);

            var data = this.element.data('value');
            if (data && data.columnName) {
                this.element.one('changed', _.bind(this._renderChangeStateChoice, this, data));
            } else {
                this.element.data('value', data);
            }

            this._on(this.$fieldChoice, {
                changed: function(e, fieldId) {
                    this.element.data('value', {});
                    if (this.auditFilter) {
                        this.element.one('changed', _.bind(this.auditFilter.reset, this.auditFilter));
                    }
                    this._renderChangeStateChoice();
                }
            });
        },

        _renderChangeStateChoice: function(data) {
            if (this.$changeStateChoice) {
                return;
            }

            data = data || $.extend(true, {
                criterion: {
                    data: {
                        auditFilter: {
                            type: 'changed'
                        }
                    }
                }
            }, this.element.data('value'));

            this.auditTypeFilter = new ChoiceFilter({
                caret: '',
                templateSelector: '#simple-choice-filter-template-embedded',
                choices: {
                    changed: __('oro.dataaudit.data_audit_condition.changed'),
                    changed_to_value: __('oro.dataaudit.data_audit_condition.changed_to_value')
                }
            });
            this.auditTypeFilter.setValue({
                type: data.criterion.data.auditFilter.type
            });
            this.auditTypeFilter.on('update', _.bind(this._onUpdate, this));

            this.$changeStateChoice = $('<span>')
                .css('display', 'inline-block')
                .html(this.auditTypeFilter.render().$el);
            this.$interval = $('<span>').html(__('oro.dataaudit.data_audit_condition.in_the_interval'));
            this.$value = $('<span>').html(__('oro.dataaudit.data_audit_condition.value'));
            this.$valueThat = $('<span>').html(__('oro.dataaudit.data_audit_condition.value_that'));

            var filterOptions = _.findWhere(this.options.filters, {
                type: 'datetime'
            });

            if (!filterOptions) {
                throw new Error('Cannot find filter "datetime"');
            }

            filterOptions.criteriaValueSelectors = {};
            $.extend(filterOptions.criteriaValueSelectors, DateTimeFilter.prototype.criteriaValueSelectors, {
                date_type: 'select[name=datetime]'
            });

            this.auditFilter = new (DateTimeFilter.extend(filterOptions))();
            this.auditFilter.value = data.criterion.data.auditFilter.data;
            this.auditFilter.on('update', _.bind(this._onUpdate, this));

            this.$auditFilterContainer = $('<div class="active-filter">').html(this.auditFilter.render().$el);
            this.$filterContainer.after(this.$auditFilterContainer);

            this._on(this.auditFilter.$el, {
                change: function() {
                    this.auditFilter.applyValue();
                }
            });
            var onChangeCb = {
                'changed': this._renderChangedChoice,
                'changed_to_value': this._renderChangedToValueChoice
            };
            this._on(this.auditTypeFilter.$el, {
                change: function() {
                    this.auditTypeFilter.applyValue();
                    onChangeCb[this.auditTypeFilter.value.type].apply(this);
                }
            });
            onChangeCb[this.auditTypeFilter.value.type].apply(this);

            this.element.data('value', data);
        },

        _renderChangedChoice: function() {
            this.$filterContainer.hide();
            this.$auditFilterContainer.css('display', 'inline');
            this.auditFilter.$el.find('> .dropdown:last').before(this.$changeStateChoice);
            this.$interval.prevUntil().show();
            this.$changeStateChoice.after(this.$interval);
        },

        _renderChangedToValueChoice: function() {
            this.$filterContainer.show();
            this.$auditFilterContainer.css('display', 'block');
            this.filter.$el.find('> .dropdown:last').before(this.$changeStateChoice);

            var selectedField = this.element.find('input.select').inputWidget('val');
            var conditions = this.$fieldChoice.fieldChoice('getApplicableConditions', selectedField);

            if (_.contains(['date', 'datetime'], conditions.type)) {
                this.$changeStateChoice.after(this.$value);
            } else {
                this.$changeStateChoice.after(this.$valueThat);
            }

            this.auditFilter.$el.find('> .dropdown:last').before(this.$interval);
            this.$interval.prevUntil().hide();
        },

        _getFilterCriterion: function() {
            var filter = {
                filter: this.filter.name,
                data: this.filter.getValue()
            };

            if (this.filter.filterParams) {
                filter.params = this.filter.filterParams;
            }

            var auditFilter = {};
            if (this.auditFilter) {
                auditFilter.columnName = this.element.find('input.select').inputWidget('val');
                auditFilter.data = this.auditFilter.getValue();

                if (this.auditFilter.filterParams) {
                    auditFilter.params = this.auditFilter.filterParams;
                }
            }
            if (this.$changeStateChoice) {
                auditFilter.type = this.auditTypeFilter.value.type;
            }

            return {
                filter: 'audit',
                data: {
                    filter: filter,
                    auditFilter: auditFilter
                }
            };
        },

        _appendFilter: function() {
            var data = this.element.data('value');

            if (data && data.criterion && data.criterion.data.filter) {
                var fieldConditionData = $.extend(true, {
                    criterion: {
                        data: {
                            filter: {
                                columnName: data.columnName
                            }
                        }
                    }
                }, data);

                fieldConditionData.columnName = data.columnName;
                this.element.data('value', {
                    columnName: data.columnName,
                    criterion: fieldConditionData.criterion.data.filter
                });
            } else {
                this.element.data('value', {});
            }

            if (this.$changeStateChoice) {
                this.$changeStateChoice.detach();
            }

            this._superApply(arguments);

            if (this.auditTypeFilter) {
                this.auditTypeFilter.$el.trigger('change');
            }

            this.element.data('value', data);
        },

        _onUpdate: function() {
            if (!this.auditFilter || !this.auditFilter.value || this.auditFilter.isEmptyValue()) {
                return this._superApply(arguments);
            }

            var value = {
                columnName: this.element.find('input.select').inputWidget('val'),
                criterion: this._getFilterCriterion()
            };

            this.element.data('value', value);
            this.element.trigger('changed');
        },

        _destroy: function() {
            this._superApply(arguments);
            if (this.auditFilter) {
                this.auditFilter.dispose();
                delete this.auditFilter;
            }
        }
    });

    return $;
});
