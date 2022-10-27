define(function(require) {
    'use strict';

    const UnreadEmailsStateHolder = require('oroemail/js/app/unread-emails-state-holder');
    return {
        init: function(deferred, options) {
            options.gridPromise.done(grid => {
                UnreadEmailsStateHolder.getModel().on('change:ids', this._changeHandler.bind(this, grid.collection));
                deferred.resolve();
            }).fail(function() {
                deferred.reject();
            });
        },
        _changeHandler: function(collection, model, ids) {
            collection.each(function(model) {
                const rowClassName = ids.indexOf(Number(model.get('id'))) === -1 ? 'email-row-is-read' : '';
                model.set('row_class_name', rowClassName);
            });
        }
    };
});
