define(function(require) {
    'use strict';

    /**
     * Updates single property during transition
     */
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const AbstractTransition = require('./abstract-transition');
    const mediator = require('oroui/js/mediator');

    const UpdateMainPropertyTransition = AbstractTransition.extend({
        constructor: function UpdateMainPropertyTransition(options) {
            UpdateMainPropertyTransition.__super__.constructor.call(this, options);
            this.propertyName = options.propertyName;
            this.boardPlugin = options.boardPlugin;
        },

        /**
         * @inheritdoc
         */
        start: function() {
            const transition = this;
            const model = this.model;
            const boardCollection = this.boardCollection;
            const properties = this.getPropertiesUpdates();
            const restoreUpdate = this.getRestoreUpdates();
            const boardCollectionUpdate = this.relativePosition;
            boardCollectionUpdate.properties = properties;
            // move item on screen
            boardCollection.updateBoardItem(model, boardCollectionUpdate);

            model.set({
                transitionStatus: 'in_progress',
                transitionStatusUpdateTime: new Date()
            });

            // start saving
            return this.apiAccessor.send(model.toJSON(), properties, {}, {
                processingMessage: __('oro.form.inlineEditing.saving_progress')
            }).then(function(response) {
                model.set(_.extend({
                    transitionStatus: 'success',
                    transitionStatusUpdateTime: new Date()
                }, response.fields || {}));
                mediator.execute('showFlashMessage', 'success', __('oro.form.inlineEditing.successMessage'), {
                    delay: transition.boardPlugin.view.earlyChangeTimeout
                });
                return true;
            }, function(response) {
                // move item on screen
                boardCollection.updateBoardItem(model, restoreUpdate);
                model.set({
                    transitionStatus: 'error',
                    transitionStatusUpdateTime: new Date()
                });
                mediator.execute('showMessage', 'error', response.responseJSON.message, {
                    onClose: function() {
                        model.set({
                            transitionStatus: 'not_started',
                            transitionStatusUpdateTime: new Date()
                        });
                    }
                });
            });
        },

        /**
         * @return {{}} - properties to update
         */
        getPropertiesUpdates: function() {
            const updates = {};
            updates[this.propertyName] = this.column.get('columnDefinition').ids[0];
            return updates;
        },

        /**
         * @return {{}} - board update configuration
         */
        getRestoreUpdates: function() {
            const columns = this.boardPlugin.getColumns();
            const initialColumn = columns.find(column => {
                return column.get('ids').indexOf(this.model.get(this.propertyName)) !== -1;
            });
            const itemIndex = initialColumn.get('items').indexOf(this.model);
            const insertAfter = itemIndex === 0 ? void 0 : initialColumn.get('items').at(itemIndex - 1);
            return {
                insertAfter: insertAfter,
                properties: this.getRestorePropertiesUpdates()
            };
        },

        /**
         * @return {{}} - properties to update
         */
        getRestorePropertiesUpdates: function() {
            const updates = {};
            updates[this.propertyName] = this.model.get(this.propertyName);
            return updates;
        }
    }, {
        build: function(model, column, boardPlugin, relativePosition) {
            const apiAccessorConfiguration = column.get('columnDefinition').transition.save_api_accessor;
            return new UpdateMainPropertyTransition({
                model: model,
                propertyName: boardPlugin.options.group_by,
                column: column,
                boardPlugin: boardPlugin,
                boardCollection: boardPlugin.getBoardCollection(),
                relativePosition: relativePosition,
                apiAccessor: new apiAccessorConfiguration.class(apiAccessorConfiguration)
            });
        }
    });

    return UpdateMainPropertyTransition;
});
