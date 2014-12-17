/*jslint browser:true, nomen:true*/
/*global define, alert*/
define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view',
    'routing',
    'orolocale/js/formatter/datetime'
], function ($, _, BaseView, routing, dateTimeFormatter) {
    'use strict';

    var CommentView;

    CommentView = BaseView.extend({
        options: {
            configuration: {},
            template: null,
            urls: {
                viewItem: null,
                updateItem: null,
                deleteItem: null
            }
        },
        attributes: {
            'class': 'list-item'
        },
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.collapsed = true;
            if (this.options.template) {
                this.template = _.template($(this.options.template).html());
            }
        },
        onEdit: function () {
            this.model.collection.trigger('onAdd', this.model);
        }
    });

    return CommentView;
});
