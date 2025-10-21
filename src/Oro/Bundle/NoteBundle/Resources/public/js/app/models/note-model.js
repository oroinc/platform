import BaseModel from 'oroui/js/app/models/base/model';

const NoteModel = BaseModel.extend({
    defaults: {
        id: '',
        message: '',
        createdAt: '',
        updatedAt: '',
        hasUpdate: false,
        editable: false,
        removable: false,
        createdBy: null,
        createdBy_id: null,
        createdBy_viewable: false,
        createdBy_avatarPicture: [],
        updatedBy: null,
        updatedBy_id: null,
        updatedBy_viewable: false,
        updatedBy_avatarPicture: []
    },

    /**
     * @inheritdoc
     */
    constructor: function NoteModel(attrs, options) {
        NoteModel.__super__.constructor.call(this, attrs, options);
    }
});

export default NoteModel;
