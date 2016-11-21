define([
    'jquery',
    'oroactivity/js/app/views/activity-context-activity-view'
], function($, BaseView) {
    'use strict';

    var ActivityContextActivityView;

    ActivityContextActivityView = BaseView.extend({
        initialize: function() {
            this.on('render', function(){
                var $icons =this.$el.find('i.icon-remove');

                if (this.collection.length == 1) {
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

