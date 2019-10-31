define(function(require) {
    'use strict';

    const _ = require('underscore');
    const UnreadEmailsStateHolder = require('oroemail/js/app/unread-emails-state-holder');
    return {
        init: function(deferred, options) {
            options.gridPromise.done(_.bind(function(grid) {
                UnreadEmailsStateHolder.getModel().on('change:ids', _.bind(this._changeHandler, this, grid.collection));
                deferred.resolve();
            }, this)).fail(function() {
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
