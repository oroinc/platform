define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const routing = require('routing');
    const CallbackListener = require('orodatagrid/js/datagrid/listener/callback-listener');
    const WidgetManager = require('oroui/js/widget-manager');
    const MultipleEntityModel = require('oroform/js/multiple-entity/model');
    const _ = require('underscore');
    const $ = require('jquery');

    const MultipleEntityComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat([
            'wid', 'addedVal', 'removedVal', 'gridName', 'columnName', 'fieldTitles', 'extraData', 'link', 'entityName',
            'fieldName'
        ]),

        /**
         * @inheritdoc
         */
        constructor: function MultipleEntityComponent(options) {
            MultipleEntityComponent.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            MultipleEntityComponent.__super__.initialize.call(this, options);

            this.addedModels = {};

            this._bindEvent();
            this._initializeCallback();
        },

        _bindEvent: function() {
            const self = this;
            WidgetManager.getWidgetInstance(this.wid, function(widget) {
                widget.getAction('select', 'adopted', function(selectBtn) {
                    selectBtn.click(function() {
                        const addedVal = $(self.addedVal).val();
                        const removedVal = $(self.removedVal).val();
                        const appendedIds = addedVal.length ? addedVal.split(',') : [];
                        const removedIds = removedVal.length ? removedVal.split(',') : [];
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
            const id = model.get('id');
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
            let label = '';

            if (!_.isUndefined(this.fieldTitles)) {
                for (let i = 0; i < this.fieldTitles.length; i++) {
                    const field = model.get(this.fieldTitles[i]);
                    if (field) {
                        label += field + ' ';
                    }
                }
            }

            return label;
        },

        _getExtraData: function(model) {
            const extraData = [];

            if (!_.isUndefined(this.extraData)) {
                for (let j = 0; j < this.extraData.length; j++) {
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

            MultipleEntityComponent.__super__.dispose.call(this);
        }
    });

    return MultipleEntityComponent;
});
