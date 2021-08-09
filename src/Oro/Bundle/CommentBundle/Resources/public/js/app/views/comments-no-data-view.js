define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('text-loader!orocomment/templates/comment/comments-no-data.html');

    const CommentsNoDataView = BaseView.extend({
        template: template,

        listen: {
            'add collection': 'render',
            'remove collection': 'render',
            'reset collection': 'render',
            'syncStateChange collection': 'render',
            'stateChange collection': 'render'
        },

        /**
         * @inheritdoc
         */
        constructor: function CommentsNoDataView(options) {
            CommentsNoDataView.__super__.constructor.call(this, options);
        }
    });

    return CommentsNoDataView;
});
