define(function(require) {
    'use strict';

    var _ = require('underscore');
    var UnreadEmailsStateHolder = require('oroemail/js/app/unread-emails-state-holder');
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
                var rowClassName = ids.indexOf(Number(model.get('id'))) === -1 ? 'email-row-is-readed' : '';
                model.set('row_class_name', rowClassName);
            });
        }
    };
});
