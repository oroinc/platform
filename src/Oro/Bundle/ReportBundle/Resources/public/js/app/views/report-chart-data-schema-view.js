define(function(require) {
    'use strict';

    var ReportChartDataSchemaView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');
    var BaseView = require('oroui/js/app/views/base/view');
    var Select2View = require('oroform/js/app/views/select2-view');

    ReportChartDataSchemaView = BaseView.extend({
        autoRender: true,

        optionsTemplate: _.template('<%= field %>(<%= group %>,<%= name %>,<%= type %>)'),

        /**
         * @type {EntityStructureDataProvider}
         */
        dataProvider: null,

        /**
         * @type {EntityFieldsCollection}
         */
        columnsCollection: null,

        defaults: {
            fieldsTableIdentifier: 'item-container'
        },

        /**
         * @inheritDoc
         */
        constructor: function ReportChartDataSchemaView() {
            ReportChartDataSchemaView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var optionNames = _.keys(this.defaults);
            _.extend(this, _.defaults(_.pick(options, optionNames), this.defaults));
            ReportChartDataSchemaView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.dataProvider;
            delete this.columnsCollection;
            ReportChartDataSchemaView.__super__.dispose.call(this);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            this._deferredRender();
            this._whenColumnsCollectionIsReady().then(function() {
                this._render();
                this._resolveDeferredRender();
            }.bind(this));
            return this;
        },

        /**
         * Waits for columns collection with data provider to get defined
         *
         * @return {JQueryPromise}
         * @protected
         */
        _whenColumnsCollectionIsReady: function() {
            var deferred = $.Deferred();
            var eventName = 'items-manager:table:reset:' + this.fieldsTableIdentifier;
            this.listenToOnce(mediator, eventName, function(collection) {
                this.columnsCollection = collection;
                this.dataProvider = collection.dataProvider;
                this.listenTo(collection, 'reset change remove', this._updateSelectedValue);
                deferred.resolve();
            }.bind(this));
            return deferred.promise();
        },

        /**
         * Performs actual rendering of view, creates subviews for all inner controls
         * @protected
         */
        _render: function() {
            var ftid = this.$el.data('ftid');
            this.$el.find('input[type=text][id^="' + ftid + '_"]').each(function(i, el) {
                var exclude = this.$(el).data('type-filter');
                var fieldSelector = new Select2View({
                    el: el,
                    select2Config: this._prepareSelect2Options(exclude)
                });
                this.subview('selector:' + fieldSelector.cid, fieldSelector);
            }.bind(this));
        },

        /**
         * Updates values of controls subviews
         * @protected
         */
        _updateSelectedValue: function() {
            var columns = this.columnsCollection.clone().removeInvalidModels().toJSON();
            _.each(this.subviewsByName, function(view, viewName) {
                var value;
                if (viewName.substr(0, 9) !== 'selector:' || !(value = view.getValue())) {
                    return;
                }
                var index = value.indexOf('(');
                var name = index > 0 ? value.substr(0, index) : value;
                if (!_.findWhere(columns, {name: name})) {
                    view.setValue('');
                }
            });
        },

        /**
         * Prepares configuration options for select2 control
         *
         * @param {Array} exclude
         * @return {Object}
         * @protected
         */
        _prepareSelect2Options: function(exclude) {
            return {
                collapsibleResults: true,
                placeholder: __('oro.entity.form.choose_entity_field'),
                data: this.data.bind(this, exclude),
                initSelection: function(element, callback) {
                    var value = element.val();
                    var index = value.indexOf('(');
                    var fieldId = index > 0 ? value.substr(0, index) : value;
                    var node = _.last(this.dataProvider.pathToEntityChainSafely(fieldId));
                    callback({
                        id: value,
                        text: node.field.label
                    });
                }.bind(this)
            };
        },

        /**
         * Prepares data for select2 control
         *
         * @param {Array} exclude
         */
        data: function(exclude) {
            var data = {
                more: false,
                results: []
            };

            var columns = this.columnsCollection.clone().removeInvalidModels().toJSON();
            var optionsTemplate = this.optionsTemplate;

            _.each(columns, function(column) {
                var options = column.func;
                var chain = this.dataProvider.pathToEntityChainSafely(column.name).slice(1);
                var entity = chain[chain.length - 1];
                var items = data.results;
                var updatedLabel = column.label;
                if (!entity || !EntityStructureDataProvider.filterFields([entity.field], exclude).length) {
                    return;
                }
                _.each(chain, function(part) {
                    var item;
                    var id;
                    if (part.entity) {
                        item = _.findWhere(items, {path: part.path});
                        if (!item) {
                            item = {
                                text: part.field.label,
                                path: part.path,
                                children: []
                            };
                            items.push(item);
                        }
                        items = item.children;
                    } else {
                        if (options) {
                            id = optionsTemplate({
                                field: part.path,
                                group: options.name,
                                name: options.group_name,
                                type: options.group_type
                            });
                        } else {
                            id = part.path;
                        }
                        items.push({
                            text: updatedLabel ? updatedLabel : part.field.label,
                            id: id
                        });
                    }
                });
            }, this);

            return data;
        }
    });

    return ReportChartDataSchemaView;
});
