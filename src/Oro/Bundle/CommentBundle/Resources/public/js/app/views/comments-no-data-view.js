define(function(require) {
    'use strict';

    var CommentsNoDataView;
    var BaseView = require('oroui/js/app/views/base/view');
    var template = require('text!orocomment/templates/comment/comments-no-data.html');

    CommentsNoDataView = BaseView.extend({
        template: template,

        listen: {
            'add collection': 'render',
            'remove collection': 'render',
            'reset collection': 'render',
            'syncStateChange collection': 'render',
            'stateChange collection': 'render'
        },

        /**
         * @inheritDoc
         */
        constructor: function CommentsNoDataView() {
            CommentsNoDataView.__super__.constructor.apply(this, arguments);
        }
    });

    return CommentsNoDataView;
});
