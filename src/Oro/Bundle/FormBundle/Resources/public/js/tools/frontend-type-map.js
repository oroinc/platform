import TagsViewerView from 'orotag/js/app/views/viewer/tags-view';
import TagsEditorView from 'orotag/js/app/views/editor/tags-editor-view';

const frontendTypeMap = {
    tags: {
        viewer: TagsViewerView,
        editor: TagsEditorView
    }
};

export default frontendTypeMap;
