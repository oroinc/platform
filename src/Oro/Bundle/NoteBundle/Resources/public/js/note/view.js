/*global define, alert*/
define(['underscore', 'backbone', 'routing', 'orolocale/js/formatter/datetime', 'autolinker'],
function (_, Backbone, routing, dateTimeFormatter, autolinker) {
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
            'class': 'map-item'
        },

        events: {
            'click .item-edit-button': 'editModel',
            'click .item-remove-button': 'deleteModel',
            'click .accordion-toggle': 'toggleView'
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el.attr('id', this.options.buildItemIdAttribute(this.model.id));
            this.template = _.template($(this.options.template).html());

            this.listenTo(this.model, 'destroy', this.remove);
        },

        editModel: function () {
            this.trigger('edit', this, this.model);
        },

        deleteModel: function () {
            this.trigger('delete', this, this.model);
        },

        toggleView: function (e) {
            e.preventDefault();

            var $el = $(e.currentTarget);
            $el.toggleClass('collapsed');
            $el.closest('.accordion-group').find('.collapse').toggleClass('in');
        },

        render: function (collapsed) {
            var data = this.model.toJSON();
            data['collapsed'] = _.isUndefined(collapsed) ? true : collapsed;
            data['createdAt'] = dateTimeFormatter.formatDateTime(data['createdAt']);
            data['updatedAt'] = dateTimeFormatter.formatDateTime(data['updatedAt']);
            if (data['createdBy_id'] && data['createdBy_viewable']) {
                data['createdBy_url'] = routing.generate('oro_user_view', {'id': data['createdBy_id']});
            }
            if (data['updatedBy_id'] && data['updatedBy_viewable']) {
                data['updatedBy_url'] = routing.generate('oro_user_view', {'id': data['updatedBy_id']});
            }
            data['message'] = _.escape(data['message']);
            data['brief_message'] = data['message'].replace(new RegExp('\r?\n', 'g'), ' ');
            data['message'] = data['message'].replace(new RegExp('\r?\n', 'g'), '<br />');
            data['message'] = autolinker.link(data['message'], {className: 'no-hash'});
            if (data['brief_message'].length > 200) {
                data['brief_message'] = data['brief_message'].substr(0, 200);
            }
            data['brief_message'] = autolinker.link(data['brief_message'], {className: 'no-hash'});

            this.$el.append(this.template(data));

            return this;
        }
    });
});
