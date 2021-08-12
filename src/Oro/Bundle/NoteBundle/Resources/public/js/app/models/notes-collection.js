define([
    'oroui/js/app/models/base/collection',
    './note-model'
], function(BaseCollection, NoteModel) {
    'use strict';

    const NotesCollection = BaseCollection.extend({
        model: NoteModel,

        baseUrl: '',

        sorting: 'DESC',

        /**
         * @inheritdoc
         */
        constructor: function NotesCollection(...args) {
            NotesCollection.__super__.constructor.apply(this, args);
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
