import BaseView from 'oroactivity/js/app/views/activity-context-activity-view';

const ActivityContextActivityView = BaseView.extend({
    /**
     * @inheritdoc
     */
    constructor: function ActivityContextActivityView(options) {
        ActivityContextActivityView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.on('render', function() {
            const $icons = this.$el.find('[data-role="delete-item"]');

            if (this.collection.length === 1) {
                $icons.css('visibility', 'hidden');
            } else {
                $icons.css('visibility', 'visible');
            }
        });

        ActivityContextActivityView.__super__.initialize.call(this, options);
    }
});

export default ActivityContextActivityView;
