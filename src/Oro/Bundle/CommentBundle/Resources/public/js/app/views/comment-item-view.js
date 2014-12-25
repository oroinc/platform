/*global define*/
define(function (require) {
    'use strict';

    var CommentItemView,
        __ = require('orotranslation/js/translator'),
        moment = require('moment'),
        routing = require('routing'),
        BaseView = require('oroui/js/app/views/base/view'),
        template = require('text!../../../templates/comment/comment-item-view.html');


    function timeDiff(time) {
        var diff, unit, scale = {},
            minute = scale.minute = 1000 * 60,
            hour = scale.hour = minute * 60,
            day = scale.day = hour * 24,
            month = scale.month = day * 30,
            year = scale.year = day * 365;

        time = moment(time).valueOf();
        diff = moment().valueOf() - time;

        if (diff > year) {
            unit = 'year';
        } else if (diff > month) {
            unit = 'month';
        } else if (diff > day) {
            unit = 'day';
        } else if (diff > hour) {
            unit = 'hour';
        } else {
            unit = 'minute';
        }

        diff = {
            number: Math.floor(diff / scale[unit]),
            unit: unit
        };

        return diff;
    }

    CommentItemView = BaseView.extend({
        template: template,
        tagName: 'li',
        className: 'comment-item',
        collapsed: true,

        events: {
            'click .removeRow': 'removeModel',
            'click .form-container': 'editModel',
            'click .short-comment': 'editModel',
            'shown .accordion-body': 'onToggle',
            'hidden .accordion-body': 'onToggle'
        },

        listen: {
            'change:updatedAt model': 'render'
        },

        initialize: function (options) {
            _.extend(this, _.pick(options || {}, ['accordionId']));
            CommentItemView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function () {
            var diff,
                data = CommentItemView.__super__.getTemplateData.call(this);
            data.cid = this.cid;
            data.accordionId = this.accordionId;
            data.collapsed = this.collapsed;
            if (data.createdAt) {
                diff = timeDiff(data.createdAt);
                data.createdTime = __('oro.comment.item.' + diff.unit + 's_ago', {number: diff.number}, diff.number);
            }
            if (data.updatedAt) {
                diff = timeDiff(data.updatedAt);
                data.updatedTime = __('oro.comment.item.' + diff.unit + 's_ago', {number: diff.number}, diff.number);
            }
            if (data.owner_id) {
                data.owner_url = routing.generate('oro_user_view', {id: data.owner_id});
            }
            if (data.editor_id) {
                data.editor_url = routing.generate('oro_user_view', {id: data.editor_id});
            }
            return data;
        },

        removeModel: function (e) {
            e.stopPropagation();
            this.model.destroy();
        },

        editModel: function () {
            if (!this.$('form').length) {
                // if it's not edit mode yet
                this.model.trigger('toEdit', this.model);
            }
        },

        onToggle: function (e) {
            this.collapsed = e.type === 'hidden';
        }
    });

    return CommentItemView;
});
