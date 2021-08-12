define([
    'underscore',
    'oroui/js/mediator',
    'orodatagrid/js/datagrid/listener/abstract-listener'
], function(_, mediator, AbstractListener) {
    'use strict';

    /**
     * @export  orodatagrid/js/datagrid/listener/action-form-listener
     * @class   orodatagrid.datagrid.listener.ActionFormListener
     * @extends orodatagrid.datagrid.listener.AbstractListener
     */
    const ActionFormListener = AbstractListener.extend({
        /**
         * @inheritdoc
         */
        constructor: function ActionFormListener(...args) {
            ActionFormListener.__super__.constructor.apply(this, args);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            mediator.on('datagrid:frontend:execute:' + options.gridName, this.onFrontAction, this);
            mediator.on('datagrid:mass:frontend:execute:' + options.gridName, this.onFrontMassAction, this);
        },

        onFrontAction: function(action) {
            const triggerAction = action.configuration.triggerAction;
            const collection = action.datagrid.collection;

            if (_.isUndefined(triggerAction)) {
                return;
            }

            // at the moment we support only `excludeRow`
            if (triggerAction === 'excludeRow') {
                collection.trigger('excludeRow', action.model.get('id'));
                collection.trigger('remove', action.model);
            }
        },

        onFrontMassAction: function(action) {
            const triggerAction = action.configuration.triggerAction;

            if (_.isUndefined(triggerAction)) {
                return;
            }

            const collection = action.datagrid.collection;
            const selectedRowsIds = action.datagrid.getSelectionState().selectedIds;

            // at the moment we support only `excludeRow`
            if (triggerAction === 'excludeRow') {
                _.each(selectedRowsIds, function(id) {
                    collection.trigger('excludeRow', id);
                    collection.trigger('remove', collection.get(id), false);
                });
                collection.fetch({reset: true});
            }
        },

        /**
         * @inheritdoc
         */
        _processValue: function(id, model) {
            // it's not being used
        }
    });

    /**
     * Builder interface implementation
     *
     * @param {jQuery.Deferred} deferred
     * @param {Object} options
     * @param {jQuery} [options.$el] container for the grid
     * @param {string} [options.gridName] grid name
     * @param {Object} [options.gridPromise] grid builder's promise
     * @param {Object} [options.data] data for grid's collection
     * @param {Object} [options.metadata] configuration for the grid
     */
    ActionFormListener.init = function(deferred, options) {
        const gridInitialization = options.gridPromise;

        gridInitialization.done(function(grid) {
            const listenerOptions = {
                $gridContainer: grid.$el,
                gridName: grid.name,
                grid: grid
            };

            const listener = new ActionFormListener(listenerOptions);
            deferred.resolve(listener);
        }).fail(function() {
            deferred.reject();
        });
    };

    return ActionFormListener;
});
