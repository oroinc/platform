define(function(require) {
    'use strict';

    var DataAuditConditionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var FieldConditionView = require('oroquerydesigner/js/app/views/field-condition-view');
    var AuditFilter = require('orodataaudit/js/audit-filter');

    DataAuditConditionView = FieldConditionView.extend({
        /**
         * @inheritDoc
         */
        constructor: function DataAuditConditionView() {
            DataAuditConditionView.__super__.constructor.apply(this, arguments);
        },

        _ensureAuditFilter: function(auditFilterConfig) {
            if (!this.auditFilter) {
                this._renderAuditFilter(auditFilterConfig);
            }
        },

        _renderAuditFilter: function(auditFilterConfig) {
            var data = $.extend(true, {
                criterion: {
                    data: {
                        auditFilter: _.extend({type: 'changed'}, auditFilterConfig)
                    }
                }
            }, this.getValue());

            var filterOptions = _.findWhere(this.options.filters, {
                type: 'datetime'
            });

            if (!filterOptions) {
                throw new Error('Cannot find filter "datetime"');
            }

            var AuditFilterConstructor = AuditFilter.extend(filterOptions);
            this.auditFilter = new AuditFilterConstructor({
                auditFilterType: data.criterion.data.auditFilter.type
            });

            var auditFilterValue = _.result(data.criterion.data.auditFilter, 'data');
            if (auditFilterValue) {
                this.auditFilter.value = auditFilterValue;
            }
            this.listenTo(this.auditFilter, {
                'update': this._onUpdate,
                'audit-type-change': function() {
                    this._setAuditTypeState(this.auditFilter.getAuditType());
                    this._onUpdate();
                }
            });

            this.auditFilter.render();
            if (this.filter) {
                this.auditFilter.wrapFilter(this.filter.$el);
            } else {
                this.$filterContainer.empty().append(this.auditFilter.$el);
                this.$filterContainer = this.auditFilter.getOriginalFilterContainer();
            }

            this._setAuditTypeState(this.auditFilter.getAuditType());
            this.setValue(data);
        },

        _setAuditTypeState: function(auditType) {
            var changedToValueMode = auditType === 'changed_to_value';
            this.$el.removeClass('date-condition-type')
                .toggleClass('changed-to-value-mode', changedToValueMode)
                .toggleClass('changed-value-mode', !changedToValueMode);
            this.auditFilter._updateTooltipVisibility(changedToValueMode ? '' : 'value');
            if (changedToValueMode) {
                var selectedField = this.$('input.select').inputWidget('val');
                var conditions = this.subview('choice-input').getApplicableConditions(selectedField);
                if (_.contains(['date', 'datetime'], conditions.type)) {
                    this.$el.addClass('date-condition-type');
                }
            }
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
                auditFilter = {
                    columnName: this.$('input.select').inputWidget('val'),
                    data: this.auditFilter.getValue(),
                    type: this.auditFilter.auditTypeFilter.value.type
                };
                if (this.auditFilter.filterParams) {
                    auditFilter.params = this.auditFilter.filterParams;
                }
            }

            return {
                filter: 'audit',
                data: {
                    filter: filter,
                    auditFilter: auditFilter
                }
            };
        },

        _appendFilter: function(filter) {
            var auditFilterConfig;
            var data = this.getValue();
            if (data && data.criterion && data.criterion.data.auditFilter) {
                auditFilterConfig = data.criterion.data.auditFilter;
            }
            if (data && data.criterion && data.criterion.data.filter) {
                var criterion = $.extend(true, {
                    columnName: data.columnName
                }, data.criterion.data.filter);

                this.setValue({
                    columnName: data.columnName,
                    criterion: criterion
                });
            } else {
                this.setValue({});
            }

            if (this.auditFilter) {
                this.auditFilter.dispose();
                delete this.auditFilter;
            }

            DataAuditConditionView.__super__._appendFilter.call(this, filter);

            this._ensureAuditFilter(auditFilterConfig);

            if (this.auditFilter && this.auditFilter.auditTypeFilter) {
                this.auditFilter.auditTypeFilter.$el.trigger('change');
            }

            this.setValue(data);
        },

        _hasEmptyFilter: function() {
            var isEmptyFilter = DataAuditConditionView.__super__._hasEmptyFilter.call(this);
            var isEmptyAuditFilter = !_.result(this.auditFilter, 'value') || this.auditFilter.isEmptyValue();
            return isEmptyFilter && isEmptyAuditFilter;
        }
    });

    return DataAuditConditionView;
});
