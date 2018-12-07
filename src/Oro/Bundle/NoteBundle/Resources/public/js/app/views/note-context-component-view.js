define([
    'jquery',
    'oroactivity/js/app/views/activity-context-activity-view'
], function($, BaseView) {
    'use strict';

    var ActivityContextActivityView;

    ActivityContextActivityView = BaseView.extend({
        /**
         * @inheritDoc
         */
        constructor: function ActivityContextActivityView() {
            ActivityContextActivityView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.on('render', function() {
                var $icons = this.$el.find('[data-role="delete-item"]');

                if (this.collection.length === 1) {
                    $icons.css('visibility', 'hidden');
                } else {
                    $icons.css('visibility', 'visible');
                }
            });

            ActivityContextActivityView.__super__.initialize.apply(this, arguments);
        }
    });

    return ActivityContextActivityView;
});

