/*jslint browser:true, nomen:true*/
/*global define, alert*/
define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view',
    'routing'
], function ($, _, BaseView, routing) {
    'use strict';

    var CommentFormView;

    CommentFormView = BaseView.extend({
        autoRender: true,
        options: {
            configuration: {},
            contentHTML: ''
        },
        events: {
            'click #add-comment-button': 'onAdd'
        },
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
        },
        onAdd: function () {
            this.model.collection.trigger('onAdd', this.model);
        },
        render: function () {
            this.$el.find('.accordion-body .message .comment').html(this.options.contentHTML);
        }
    });

    return CommentFormView;

});
