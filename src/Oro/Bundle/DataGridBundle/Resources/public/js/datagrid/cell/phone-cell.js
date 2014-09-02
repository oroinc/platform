/*jslint nomen:true*/
/*global define*/
define([
    'jquery',
    'backbone',
    'backgrid'
], function ($, Backbone, Backgrid) {
    "use strict";

    var PhoneCell;

    /**
     * Renders a link with tel protocol.
     *
     * @export  oro/datagrid/cell/phone-cell
     * @class   oro.datagrid.cell.PhoneCell
     * @extends Backbone.View
     */
    PhoneCell = Backbone.View.extend({

        /** @property */
        className: "phone-cell",

        /** @property */
        tagName: "td",

        /** @property */
        events: {
            "click": "stopPropagation"
        },

        /**
         * Initializer. If the underlying model triggers a `select` event, this cell
         * will change its checked value according to the event's `selected` value.
         *
         * @param {Object} options
         * @param {Backgrid.Column} options.column
         * @param {Backbone.Model} options.model
         */
        initialize: function (options) {
            Backgrid.requireOptions(options, ["model", "column"]);

            this.column = options.column;
            if (!(this.column instanceof Backgrid.Column)) {
                this.column = new Backgrid.Column(this.column);
            }
        },

        /**
         * If don't stop propagation click will select row
         */
        stopPropagation: function (e) {
            e.stopPropagation();
        },

        /**
         * Renders phone
         */
        render: function () {
            var phone = (this.model.get(this.column.get("name")) || "");

            this.$el.html("<a href=\"tel:" + phone + "\">" + phone + "</a>");
            return this;
        }
    });

    return PhoneCell;
});
