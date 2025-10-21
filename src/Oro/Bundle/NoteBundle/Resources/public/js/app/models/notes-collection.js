import BaseCollection from 'oroui/js/app/models/base/collection';
import NoteModel from './note-model';

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

export default NotesCollection;
