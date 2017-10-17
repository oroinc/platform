define(function(require) {
    'use strict';

    var MultipleEntityComponent;
    var BaseView = require('oroui/js/app/views/base/view');
    var routing = require('routing');
    var CallbackListener = require('orodatagrid/js/datagrid/listener/callback-listener');
    var WidgetManager = require('oroui/js/widget-manager');
    var MultipleEntityModel = require('oroform/js/multiple-entity/model');

    MultipleEntityComponent = BaseView.extend({
        initialize: function(options) {
            _.extend(this, _.pick(options, [
                'wid', 'gridName', 'fieldTitles', 'entityName', 'fieldName', 'extraData'
            ]));

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
                        var addedVal = $('#appendRelation').val();
                        var removedVal = $('#removeRelation').val();
                        var appendedIds = addedVal.length ? addedVal.split(',') : [];
                        var removedIds = removedVal.length ? removedVal.split(',') : [];
                        widget.trigger('completeSelection', appendedIds, self.addedModels, removedIds);
                    });
                });
            });
        },

        _initializeCallback: function() {
            new CallbackListener({
                $gridContainer: $('[data-wid="' + this.wid + '"]'),
                gridName: this.gridName,
                dataField: 'id',
                columnName: 'assigned',
                processCallback: function(value, model, listener) {
                    var id = model.get('id');
                    if (model.get(listener.columnName)) {
                        var label = '';

                        for (var fieldTitle in this.fieldTitles) {
                            var field = model.get(this.fieldTitles[fieldTitle]);
                            if (field) {
                                label += field + ' ';
                            }
                        }

                        var extraData = [];
                        for (var data in this.extraData) {
                            extraData.push({
                                'label': this.extraData[data].label,
                                'value': model.get(this.extraData[data].value)
                            });
                        }

                        this.addedModels[id] = new MultipleEntityModel({
                            'id': model.get('id'),
                            'link': routing.generate(
                                'oro_entity_detailed',
                                {
                                    id: model.get('id'),
                                    entityName: this.entityName,
                                    fieldName: this.field_name
                                }
                            ),
                            'label': label,
                            'extraData': extraData
                        });
                    } else if (this.addedModels.hasOwnProperty(id)) {
                        delete this.addedModels[id];
                    }
                }.bind(this)
            });
        }
    });

    return MultipleEntityComponent;
});
