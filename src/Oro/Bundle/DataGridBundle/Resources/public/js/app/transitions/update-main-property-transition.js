define(function(require) {
    'use strict';

    /**
     * Updates single property during transition
     */
    var UpdateMainPropertyTransition;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var AbstractTransition = require('./abstract-transition');
    var mediator = require('oroui/js/mediator');

    UpdateMainPropertyTransition = AbstractTransition.extend({
        constructor: function(options) {
            UpdateMainPropertyTransition.__super__.constructor.call(this, options);
            this.propertyName = options.propertyName;
            this.boardPlugin = options.boardPlugin;
        },

        /**
         * @inheritDoc
         */
        start: function() {
            var transition = this;
            var model = this.model;
            var boardCollection = this.boardCollection;
            var properties = this.getPropertiesUpdates();
            var restoreUpdate = this.getRestoreUpdates();
            var boardCollectionUpdate = this.relativePosition;
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
            var updates = {};
            updates[this.propertyName] = this.column.get('columnDefinition').ids[0];
            return updates;
        },

        /**
         * @return {{}} - board update configuration
         */
        getRestoreUpdates: function() {
            var _this = this;
            var columns = this.boardPlugin.getColumns();
            var initialColumn = columns.find(function(column) {
                return column.get('ids').indexOf(_this.model.get(_this.propertyName)) !== -1;
            });
            var itemIndex = initialColumn.get('items').indexOf(this.model);
            var insertAfter = itemIndex === 0 ? void 0 : initialColumn.get('items').at(itemIndex - 1);
            return {
                insertAfter: insertAfter,
                properties: this.getRestorePropertiesUpdates()
            };
        },

        /**
         * @return {{}} - properties to update
         */
        getRestorePropertiesUpdates: function() {
            var updates = {};
            updates[this.propertyName] = this.model.get(this.propertyName);
            return updates;
        }
    }, {
        build: function(model, column, boardPlugin, relativePosition) {
            var apiAccessorConfiguration = column.get('columnDefinition').transition.save_api_accessor;
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
