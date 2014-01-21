/* global define */
define(['underscore', 'backbone', 'oro/translator', 'oro/query-designer/abstract-view', 'oro/query-designer/filter/collection', 'oro/query-designer/filter-builder'],
function(_, Backbone, __, AbstractView, FilterCollection, filterBuilder) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/query-designer/filter/view
     * @class   oro.queryDesigner.filter.View
     * @extends oro.queryDesigner.AbstractView
     */
    return AbstractView.extend({
        /** @property oro.queryDesigner.filter.Collection */
        collectionClass: FilterCollection,

        /** @property {jQuery} */
        criterionSelector: null,

        /** @property {oro.queryDesigner.FilterManager} */
        filterManager: null,

        initialize: function() {
            AbstractView.prototype.initialize.apply(this, arguments);

            this.addFieldLabelGetter(this.getCriterionFieldLabel);
        },

        initForm: function() {
            AbstractView.prototype.initForm.apply(this, arguments);

            this.criterionSelector = this.form.find('[data-purpose="criterion-selector"]');

            // load filters
            this.criterionSelector.hide();
            filterBuilder.init(this.criterionSelector.parent(), _.bind(function (filterManager) {
                this.filterManager = filterManager;
                this.listenTo(this.filterManager, "update_value", this.onCriterionValueUpdated);
                this.trigger('filter_manager_initialized');
            }, this));

            // set criterion selector when a column changed
            this.columnSelector.$el.on('change', _.bind(function (e) {
                if (!_.isUndefined(e.added)) {
                    if (_.isNull(this.filterManager) && !_.isUndefined(console)) {
                        console.error('Cannot choose a filer because the filter manager was not initialized yet.');
                    } else {
                        this.filterManager.setActiveFilter(
                            this.columnSelector.getFieldApplicableConditions(e.added.id)
                        );
                    }
                }
            }, this));

            // set criterion selector when underlined input control changed
            this.criterionSelector.on('change', _.bind(function (e) {
                if (_.isNull(this.filterManager) && !_.isUndefined(console)) {
                    console.error('Cannot set a filter because the filter manager was not initialized yet.');
                } else {
                    if (e.currentTarget.value == '') {
                        this.filterManager.reset();
                    } else {
                        this.filterManager.setActiveFilter(JSON.parse(e.currentTarget.value));
                    }
                }
            }, this));
        },

        beforeFormSubmit: function () {
            if (!_.isNull(this.filterManager)) {
                this.filterManager.ensurePopupCriteriaClosed();
            }
            AbstractView.prototype.beforeFormSubmit.apply(this, arguments);
        },

        initModel: function (model, index) {
            AbstractView.prototype.initModel.apply(this, arguments);
            model.set('index', index + 1);
        },

        deleteModel: function(model) {
            AbstractView.prototype.deleteModel.apply(this, arguments);
            this.getCollection().each(function (m) {
                if (m.get('index') > model.get('index')) {
                    m.set('index', m.get('index') - 1);
                }
            });
        },

        onResetCollection: function () {
            if (!_.isNull(this.filterManager)) {
                AbstractView.prototype.onResetCollection.apply(this, arguments);
            } else {
                this.once('filter_manager_initialized', function() {
                    AbstractView.prototype.onResetCollection.apply(this, arguments);
                }, this);
            }
        },

        onCriterionValueUpdated: function () {
            this.criterionSelector.val(
                this.filterManager.isEmptyValue()
                    ? ''
                    : JSON.stringify({
                        filter: this.filterManager.getName(),
                        data: this.filterManager.getValue()
                      })
            );
        },

        prepareItemTemplateData: function (model) {
            var data = AbstractView.prototype.prepareItemTemplateData.apply(this, arguments);
            data['filter'] = data['columnName'] + ' ' + data['criterion'];
            return data;
        },

        getCriterionFieldLabel: function (field, name, value) {
            if (field.attr('name') == this.criterionSelector.attr('name')) {
                if (_.isNull(value) || value == '') {
                    return '';
                } else if (_.isString(value)) {
                    value = JSON.parse(value)
                }
                return this.filterManager.getCriteriaHint(value);
            }
            return null;
        },

        getFormFieldValue: function (name, field) {
            if (field.attr('name') == this.criterionSelector.attr('name')) {
                var value = field.val();
                return (value != '') ? JSON.parse(value) : null;
            }
            return AbstractView.prototype.getFormFieldValue.apply(this, arguments);
        },

        setFormFieldValue: function (name, field, value) {
            if (field.attr('name') == this.criterionSelector.attr('name')) {
                field.val(
                    (_.isNull(value) || value == '')
                        ? ''
                        : JSON.stringify(value)
                );
                return;
            }
            AbstractView.prototype.setFormFieldValue.apply(this, arguments);
        }
    });
});
