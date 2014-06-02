/*global define, alert*/
define([ 'underscore', 'backbone', 'routing', 'orolocale/js/formatter/datetime'
    ], function (_, Backbone, routing, dateTimeFormatter) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oronote/js/note/view
     * @class   oronote.note.View
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        tagName: 'div',

        options: {
            'template': null,
            'buildItemIdAttribute': null // function (id) { return string; }
        },

        attributes: {
            'class': 'note'
        },

        events: {
            'click button:has(.icon-remove)': 'close',
            'click button:has(.icon-pencil)': 'edit'
        },

        initialize: function () {
            this.$el.attr('id', this.options.buildItemIdAttribute(this.model.id));
            this.template = _.template($(this.options.template).html());

            this.listenTo(this.model, 'destroy', this.remove);
        },

        edit: function () {
            this.trigger('edit', this, this.model);
        },

        close: function () {
            this.model.destroy({wait: true});
        },

        render: function () {
            var data = this.model.toJSON();
            data['createdAt'] = dateTimeFormatter.formatDateTime(data['createdAt']);
            data['updatedAt'] = dateTimeFormatter.formatDateTime(data['updatedAt']);
            if (data['createdBy_id'] && data['createdBy_viewable']) {
                data['createdBy_url'] = routing.generate('oro_user_view', {'id': data['createdBy_id']});
            }
            if (data['updatedBy_id'] && data['updatedBy_viewable']) {
                data['updatedBy_url'] = routing.generate('oro_user_view', {'id': data['updatedBy_id']});
            }

            this.$el.append(this.template(data));

            return this;
        }
    });
});
