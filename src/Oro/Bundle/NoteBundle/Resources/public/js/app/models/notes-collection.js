define([
    'oroui/js/app/models/base/collection',
    './note-model'
], function(BaseCollection, NoteModel) {
    'use strict';

    var NotesCollection;

    NotesCollection = BaseCollection.extend({
        model: NoteModel,

        baseUrl: '',

        sorting: 'DESC',

        /**
         * @inheritDoc
         */
        constructor: function NotesCollection() {
            NotesCollection.__super__.constructor.apply(this, arguments);
        },

        url: function() {
            return this.baseUrl + '?sorting=' + this.sorting;
        },

        getSorting: function() {
            return this.sorting;
        },

        setSorting: function(mode) {
            this.sorting = mode;
        }
    });

    return NotesCollection;
});
