import BaseComponent from 'oroactivity/js/app/components/activity-context-activity-component';
import NoteActivityContextComponentView from 'oronote/js/app/views/note-context-component-view';

const ActivityContextComponent = BaseComponent.extend({
    /**
     * @inheritdoc
     */
    constructor: function ActivityContextComponent(options) {
        ActivityContextComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initView: function() {
        this.contextsView = new NoteActivityContextComponentView(this.getViewOptions());
    }
});

export default ActivityContextComponent;
