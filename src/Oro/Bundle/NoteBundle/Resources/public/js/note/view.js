/*global define, alert*/
define([ 'underscore', 'backbone'
    ], function (_, Backbone) {
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
            this.$el.append(this.template(this.model));
            return this;
        }
    });
});
