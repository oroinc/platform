define(function(require) {
    'use strict';

    var MultipleEntityComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var routing = require('routing');
    var CallbackListener = require('orodatagrid/js/datagrid/listener/callback-listener');
    var WidgetManager = require('oroui/js/widget-manager');
    var MultipleEntityModel = require('oroform/js/multiple-entity/model');
    var _ = require('underscore');
    var $ = require('jquery');

    MultipleEntityComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat([
            'wid', 'addedVal', 'removedVal', 'gridName', 'columnName', 'fieldTitles', 'extraData', 'link', 'entityName',
            'fieldName'
        ]),

        /**
         * @inheritDoc
         */
        constructor: function MultipleEntityComponent() {
            MultipleEntityComponent.__super__.constructor.apply(this, arguments);
        },

        initialize: function() {
            MultipleEntityComponent.__super__.initialize.apply(this, arguments);

            this.addedModels = {};

            this._bindEvent();
            this._initializeCallback();
        },

        _bindEvent: function() {
            var self = this;
            WidgetManager.getWidgetInstance(this.wid, function(widget) {
                widget.getAction('select', 'adopted', function(selectBtn) {
                    selectBtn.click(function() {
                        var addedVal = $(self.addedVal).val();
                        var removedVal = $(self.removedVal).val();
                        var appendedIds = addedVal.length ? addedVal.split(',') : [];
                        var removedIds = removedVal.length ? removedVal.split(',') : [];
                        widget.trigger('completeSelection', appendedIds, self.addedModels, removedIds);
                    });
                });
            });
        },

        _initializeCallback: function() {
            this.callbackListener = new CallbackListener({
                $gridContainer: $('[data-wid="' + this.wid + '"]'),
                gridName: this.gridName,
                dataField: 'id',
                columnName: this.columnName,
                processCallback: this.onModelSelect.bind(this)
            });
        },

        onModelSelect: function(value, model, listener) {
            var id = model.get('id');
            if (model.get(listener.columnName)) {
                this.addedModels[id] = new MultipleEntityModel({
                    id: model.get('id'),
                    link: routing.generate(
                        this.link,
                        {
                            id: model.get('id'),
                            entityName: this.entityName,
                            fieldName: this.field_name
                        }
                    ),
                    label: this._getLabel(model),
                    extraData: this._getExtraData(model)
                });
            } else if (this.addedModels.hasOwnProperty(id)) {
                delete this.addedModels[id];
            }
        },

        _getLabel: function(model) {
            var label = '';

            if (!_.isUndefined(this.fieldTitles)) {
                for (var i = 0; i < this.fieldTitles.length; i++) {
                    var field = model.get(this.fieldTitles[i]);
                    if (field) {
                        label += field + ' ';
                    }
                }
            }

            return label;
        },

        _getExtraData: function(model) {
            var extraData = [];

            if (!_.isUndefined(this.extraData)) {
                for (var j = 0; j < this.extraData.length; j++) {
                    extraData.push({
                        label: this.extraData[j].label,
                        value: model.get(this.extraData[j].value)
                    });
                }
            }

            return extraData;
        },

        dispose: function() {
            this.callbackListener.dispose();
            delete this.callbackListener;

            MultipleEntityComponent.__super__.dispose.apply(this, arguments);
        }
    });

    return MultipleEntityComponent;
});
